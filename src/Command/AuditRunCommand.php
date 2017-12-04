<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Registry;
use Drutiny\Policy;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Logger\ConsoleLogger;
use Drutiny\Target\Target;
use Drutiny\Check\RemediableInterface;
use Drutiny\Report\ProfileRunReport;
use Drutiny\ProfileInformation;
use Drutiny\AuditResponse\AuditResponse;


/**
 *
 */
class AuditRunCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('audit:run')
      ->setDescription('Run a single audit against a site without a policy.')
      ->addArgument(
        'audit',
        InputArgument::REQUIRED,
        'The PHP class (including namespace) of the audit'
      )
      ->addArgument(
        'target',
        InputArgument::REQUIRED,
        'The target to run the check against.'
      )
      ->addOption(
        'set-parameter',
        'p',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Set parameters for the check.',
        []
      )
      ->addOption(
        'remediate',
        'r',
        InputOption::VALUE_NONE,
        'Allow failed checks to remediate themselves if available.'
      )
      ->addOption(
        'uri',
        'l',
        InputOption::VALUE_OPTIONAL,
        'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $audit_class = $input->getArgument('audit');

    $policy = new Policy([
      'title' => 'Audit: ' . $audit_class,
      'name' => '_test',
      'class' => $audit_class,
      'description' => 'Verbatim run of an audit class',
      'remediation' => 'none',
      'success' => 'success',
      'failure' => 'failure',
      'warning' => 'warning',
    ]);

    $registry = new Registry();

    // Setup any parameters for the check.
    $parameters = [];
    foreach ($input->getOption('set-parameter') as $option) {
      list($key, $value) = explode('=', $option, 2);
      // Using Yaml::parse to ensure datatype is correct.
      $parameters[$key] = Yaml::parse($value);
    }

    // Setup the target which is usually something like a drush aliases (@sitename.env).
    list($target_name, $target_data) = Target::parseTarget($input->getArgument('target'));
    $target_class = $registry->getTargetClass($target_name);

    $result = new AuditResponse($policy);
    $result->set(FALSE, $policy->getParameterDefaults());

    // Generate the sandbox to execute the check.
    $sandbox = new Sandbox($target_class, $policy);
    $sandbox
      ->setParameters($parameters)
      ->setLogger(new ConsoleLogger($output))
      ->getTarget()
      ->parse($target_data);

    if ($uri = $input->getOption('uri')) {
      $sandbox->drush()->setGlobalDefaultOption('uri', $uri);
    }

    $response = $sandbox->run();

    // Attempt remeidation.
    if (!$response->isSuccessful() && $input->getOption('remediate') && ($sandbox->getAuditor() instanceof RemediableInterface)) {
      $response = $sandbox->remediate();
    }

    // Generate a profile so we can use the profile reporting tools.
    $profile = new ProfileInformation([
      'title' => $policy->get('title'),
    ]);

    $report = new ProfileRunReport($profile, $sandbox->getTarget(), [$response]);
    $report->render($input, $output);
  }
}
