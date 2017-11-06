<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Drutiny\Registry;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Logger\ConsoleLogger;
use Drutiny\Report;
use Drutiny\Target\Target;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 *
 */
class ProfileRunCommand extends Command {

  const EMOJI_REMEDIATION = "\xE2\x9A\xA0";

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('profile:run')
      ->setDescription('Run a profile of checks against a target.')
      ->addArgument(
        'profile',
        InputArgument::REQUIRED,
        'The name of the profile to run.'
      )
      ->addArgument(
        'target',
        InputArgument::REQUIRED,
        'The target to run the checks against.'
      )
      ->addOption(
        'remediate',
        'r',
        InputOption::VALUE_NONE,
        'Allow failed checks to remediate themselves if available.'
      )
      ->addOption(
        'format',
        'f',
        InputOption::VALUE_OPTIONAL,
        'Specify which output format to render the report (console, html, json). Defaults to console.',
        'console'
      )
      ->addOption(
        'uri',
        'l',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.'
      )
      ->addOption(
        'report-filename',
        'o',
        InputOption::VALUE_OPTIONAL,
        'For json and html formats, use this option to write report to file. Defaults to stdout.',
        'stdout'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    // Setup the target.
    list($target_name, $target_data) = Target::parseTarget($input->getArgument('target'));
    $targets = Registry::targets();
    if (!isset($targets[$target_name])) {
      throw new InvalidArgumentException("$target_name is not a valid target.");
    }

    // Setup the check.
    $profile = $input->getArgument('profile');
    $profiles = Registry::profiles();
    if (!isset($profiles[$profile])) {
      throw new InvalidArgumentException("$profile is not a valid profile.");
    }

    // Setup the reporting format.
    $format = $input->getOption('format');
    if (!in_array($format, ['console', 'json', 'html'])) {
      throw new InvalidArgumentException("Reporting format '$format' is not supported.");
    }

    // Get the URLs.
    $uris = $input->getOption('uri');
    if (empty($uris)) {
      $uris = ['default'];
    }

    $checks = Registry::policies();
    $results = [];

    $progress_bar_enabled = TRUE;

    // Disable progress bar when expecting raw json or html output.
    if (($input->getOption('report-filename') == 'stdout') && in_array($format, ['json', 'html'])) {
      $progress_bar_enabled = FALSE;
    }
    // Do not use progress bar when verbosity is in debug mode.
    if ($output->getVerbosity() > OutputInterface::VERBOSITY_VERY_VERBOSE) {
      $progress_bar_enabled = FALSE;
    }

    // Establish a progress bar for reporting since this can take sometime.
    $progress = new ProgressBar($output, count($profiles[$profile]->getPolicies()) * count($uris));
    $progress->setFormatDefinition('custom', " <comment>%message%</comment>\n %current%/%max% <info>[%bar%]</info> %percent:3s%% %memory:6s%");
    $progress->setFormat('custom');
    $progress->setMessage("Starting...");
    $progress->setBarWidth(80);
    $progress_bar_enabled && $progress->start();

    foreach ($uris as $uri) {
      foreach ($profiles[$profile]->getPolicies() as $name => $parameters) {
        $progress_bar_enabled && $progress->setMessage("[$uri] " . $checks[$name]->get('title'));
        $sandbox = new Sandbox($targets[$target_name]->class, $checks[$name]);
        $sandbox->setParameters($parameters)
          ->setLogger(new ConsoleLogger($output))
          ->getTarget()
          ->parse($target_data);

        if ($uri != 'default') {
          $sandbox->drush()->setGlobalDefaultOption('uri', $uri);
        }

        $response = $sandbox->run();

        // Attempt remeidation.
        if (!$response->isSuccessful() && $input->getOption('remediate')) {
          $progress_bar_enabled && $progress->setMessage(self::EMOJI_REMEDIATION . "   Remediating " . $checks[$name]->get('title'));
          $response = $sandbox->remediate();
        }
        $result[$uri][$name] = $response;
        $progress_bar_enabled && $progress->advance();
      }
    }

    if ($progress_bar_enabled) {
      $progress->setMessage("Done");
      $progress->finish();
      $output->writeln('');
    }

    if (count($uris) == 1) {
      switch ($format) {
        case 'json':
          $report = new Report\ProfileRunJsonReport($profiles[$profile], $sandbox->getTarget(), current($result));
          break;

        case 'html':
          $report = new Report\ProfileRunHtmlReport($profiles[$profile], $sandbox->getTarget(), current($result));
          break;

        case 'console':
        default:
          $report = new Report\ProfileRunReport($profiles[$profile], $sandbox->getTarget(), current($result));
          break;
      }
      $report->render($input, $output);
    }
    else {
      $report = new Report\ProfileRunMultisiteReport($profiles[$profile], $sandbox->getTarget(), $result);
      $report->render($input, $output);
    }
  }

}
