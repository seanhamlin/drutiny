<?php

namespace Drutiny\Command;

use Drutiny\Assessment;
use Drutiny\Config;
use Drutiny\Container;
use Drutiny\Profile\ProfileSource;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\Report;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\Registry as TargetRegistry;
use Drutiny\DomainSource;
use Drutiny\DomainList\DomainListRegistry;
use Drutiny\Target\Target;
use Drutiny\ProgressBar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *
 */
class ProfileRunCommand extends Command {
  use \Drutiny\Policy\ContentSeverityTrait;

  /**
   * @inheritdoc
   */
  protected function configure() {
    $domain_list = array_keys(Config::get('DomainList'));
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
        'The target to run the policy collection against.'
      )
      ->addOption(
        'remediate',
        'r',
        InputOption::VALUE_NONE,
        'Allow failed policy aduits to remediate themselves if available.'
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
        'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.',
        ['default']
      )
      ->addOption(
        'title',
        't',
        InputOption::VALUE_OPTIONAL,
        'Override the title of the profile with the specified value.',
        false
      )
      ->addOption(
        'exit-on-severity',
        'x',
        InputOption::VALUE_OPTIONAL,
        'Send an exit code to the console if a policy of a given severity fails. Defaults to none (exit code 0). (Options: none, low, normal, high, critical)',
        'none'
      )
      ->addOption(
        'report-filename',
        'o',
        InputOption::VALUE_OPTIONAL,
        'For json and html formats, use this option to write report to file. Drutiny will automate a filepath if the option is omitted. Use "stdout" to print to terminal',
        false
      )
      ->addOption(
        'exclude-policy',
        'e',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Specify policy names to exclude from the profile that are normally listed.',
        []
      )
      ->addOption(
        'include-policy',
        'p',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Specify policy names to include in the profile in addition to those listed in the profile.',
        []
      )
      ->addOption(
        'reporting-period-start',
        null,
        InputOption::VALUE_OPTIONAL,
        'The starting point in time to report from. Can be absolute or relative. Defaults to 24 hours before the current hour.',
        date('Y-m-d H:00:00', strtotime('-24 hours'))
      )
      ->addOption(
        'reporting-period-end',
        null,
        InputOption::VALUE_OPTIONAL,
        'The end point in time to report to. Can be absolute or relative. Defaults to the current hour.',
        date('Y-m-d H:00:00')
      )
      ->addOption(
        'report-per-site',
        null,
        InputOption::VALUE_NONE,
        'Flag to additionally render a report for each site audited in multisite mode.'
      )
      ->addOption(
        'domain-source',
        'd',
        InputOption::VALUE_OPTIONAL,
        'Use a domain source to preload uri options. Defaults to yaml filepath. Options: (' . implode(', ', $domain_list) . ')'
      );

      foreach (Config::get('DomainList') as $name => $class) {
        $options = DomainListRegistry::getOptions($name);
        foreach ($options as $param) {
          $this->addOption(
            'domain-source-' . $name . '-' . $param->name,
            null,
            InputOption::VALUE_OPTIONAL,
            $param->description
          );
        }
      }

      $this->addOption(
        'domain-source-blacklist',
        null,
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Exclude domains that match this regex filter',
        []
      )
      ->addOption(
        'domain-source-whitelist',
        null,
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Exclude domains that don\'t match this regex filter',
        []
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Ensure Container logger uses the same verbosity.
    Container::setVerbosity($output->getVerbosity());

    // Setup the check.
    $profile = ProfileSource::loadProfileByName($input->getArgument('profile'));

    $profile->setReportPerSite($input->getOption('report-per-site'));

    // Override the title of the profile with the specified value.
    if ($title = $input->getOption('title')) {
      $profile->setTitle($title);
    }

    $filepath = $input->getOption('report-filename');
    $format = $input->getOption('format');

    // If format is not out to console and the filepath isn't set, automate
    // what the filepath should be.
    if (!in_array($format, ['console', 'terminal']) && !$filepath) {
      $filepath = strtr('target-profile-date.format', [
        'target' => preg_replace('/[^a-z0-9]/', '', strtolower($input->getArgument('target'))),
        'profile' => $input->getArgument('profile'),
        'date' => date('Ymd-His'),
        'format' => $input->getOption('format')
      ]);
    }
    // If the filepath is not set for console formats, then force to stdout.
    elseif (in_array($format, ['console', 'terminal']) && !$filepath) {
      $filepath = 'stdout';
    }

    // Setup the reporting format.
    $format = $profile->getFormatOption($input->getOption('format'), [
      'output' => $filepath != 'stdout' ? $filepath : $output,
      'input' => $input
    ]);

    // Allow command line to add policies to the profile.
    $included_policies = $input->getOption('include-policy');
    foreach ($included_policies as $policy_name) {
      $policyDefinition = PolicyDefinition::createFromProfile($policy_name, count($profile->getAllPolicyDefinitions()));
      $profile->addPolicyDefinition($policyDefinition);
    }

    // Allow command line omission of policies highlighted in the profile.
    // WARNING: This may remove policy dependants which may make polices behave
    // in strange ways.
    $excluded_policies = $input->getOption('exclude-policy');
    $policyDefinitions = array_filter($profile->getAllPolicyDefinitions(), function ($policy) use ($excluded_policies) {
      return !in_array($policy->getName(), $excluded_policies);
    });

    // Setup the target.
    $target = TargetRegistry::loadTarget($input->getArgument('target'));

    // Get the URLs.
    $uris = $input->getOption('uri');

    // Load additional uris from domain-source
    if ($domains = DomainSource::loadFromInput($input)) {
      $uris = ($uris === ['default']) ? [] : $uris;
      $uris = array_merge($domains, $uris);
    }

    // Setup the progress bar to log updates.
    $steps = count($policyDefinitions) * count($uris);

    $progress = new ProgressBar($output, $steps);

    // We don't want to run the progress bar if the output is to stdout.
    // Unless the format is console/terminal as then the output doesn't matter.
    // E.g. turn of progress bar in json, html and markdown formats.
    if ($filepath == 'stdout' && !in_array($format->getFormat(), ['console', 'terminal'])) {
      $progress->disable();
    }
    // Do not use the progress bar when using a high verbosity logging output.
    elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
      $progress->disable();
    }

    $results = [];

    $start = new \DateTime($input->getOption('reporting-period-start'));
    $end   = new \DateTime($input->getOption('reporting-period-end'));
    $profile->setReportingPeriod($start, $end);

    $policies = [];
    foreach ($policyDefinitions as $policyDefinition) {
      $policies[] = $policyDefinition->getPolicy();
    }

    $progress->start();

    foreach ($uris as $uri) {
      try {
        $target->setUri($uri);
      }
      catch (\Drutiny\Target\InvalidTargetException $e) {
        Container::getLogger()->warning("Target cannot be evaluated: " . $e->getMessage());
        $progress->advance(count($policyDefinitions));
        continue;
      }

      $assessment = new Assessment($uri);
      $assessment->assessTarget($target, $policies, $start, $end, $input->getOption('remediate'));
      $results[$uri] = $assessment;
    }

    $progress->finish();

    if (!count($results)) {
      Container::getLogger()->error("No results were generated.");
      return;
    }

    $files = $format->render($profile, $target, $results);

    $console = new SymfonyStyle($input, $output);
    foreach ($files as $filepath) {
      $console->success('Report written to ' . $filepath);
    }

    $this->setSeverity($input->getOption('exit-on-severity'));

    // Do not use a non-zero exit code when no severity is set (Default).
    if (!$this->getSeverity()) {
      return;
    }

    $bad_results = 0;

    foreach ($results as $assessment) {
      if ($assessment->isSuccessful()) {
        continue;
      }

      // Increase the exit severity to match the highest assessment severity.
      if ($assessment->getSeverity() >= $this->getSeverity()) {
        $this->setSeverity($assessment->getSeverity());
        $bad_results++;
      }
    }

    // Zero exit code when no bad results.
    if (!$bad_results) {
      return;
    }

    // Return the highest found severity as exit code.
    return $this->getSeverity();
  }
}
