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
use Drutiny\PolicyChain;
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
class PolicyAuditCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('policy:audit')
      ->setDescription('Run a single policy audit against a site.')
      ->addArgument(
        'policy',
        InputArgument::REQUIRED,
        'The name of the check to run.'
      )
      ->addArgument(
        'target',
        InputArgument::REQUIRED,
        'The target to run the check against.'
      )
      ->addOption(
        'set-parameter',
        'p',
        InputOption::VALUE_OPTIONAL,
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

    $registry = new Registry();

    // Setup the check.
    $policy = $input->getArgument('policy');
    $policies = $registry->policies();
    if (!isset($policies[$policy])) {
      throw new \InvalidArgumentException("$policy is not a valid check.");
    }

    // Policy chain ensures policy dependents are included in the audit.
    $chain = new PolicyChain();
    $chain->add($policies[$policy]);

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

    $result = [];
    $failure = FALSE;

    foreach ($chain->getPolicies() as $policy) {
      if ($failure) {
        $result[$policy->get('name')] = new AuditResponse($policy);
        $result[$policy->get('name')]->set(FALSE, $policy->getParameterDefaults());
        continue;
      }
      // Generate the sandbox to execute the check.
      $sandbox = new Sandbox($target_class, $policy);

      // For the policy that
      if ($policy->get('name') == $input->getArgument('policy')) {
        $sandbox->setParameters($parameters);
      }
      $sandbox
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

      $result[$policy->get('name')] = $response;

      // Dependencies are broken, cannot progress.
      if (!$response->isSuccessful()) {
        $failure = TRUE;
      }
    }

    // Generate a profile so we can use the profile reporting tools.
    $profile = new ProfileInformation([
      'title' => 'Policy Audit: ' . $policies[$input->getArgument('policy')]->get('name'),
    ]);

    $report = new ProfileRunReport($profile, $sandbox->getTarget(), $result);
    $report->render($input, $output);
  }

}
