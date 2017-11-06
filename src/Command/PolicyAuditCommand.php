<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Drutiny\Registry;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Logger\ConsoleLogger;
use Drutiny\Target\Target;
use Drutiny\Check\RemediableInterface;
use Symfony\Component\Yaml\Yaml;

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

    // Setup the target.
    list($target_name, $target_data) = Target::parseTarget($input->getArgument('target'));

    $targets = Registry::targets();
    if (!isset($targets[$target_name])) {
      throw new InvalidArgumentException("$target_name is not a valid target.");
    }

    // Setup the check.
    $policy = $input->getArgument('policy');
    $policies = Registry::policies();
    if (!isset($policies[$policy])) {
      throw new \InvalidArgumentException("$policy is not a valid check.");
    }

    // Setup any parameters for the check.
    $parameters = [];
    foreach ($input->getOption('set-parameter') as $option) {
      list($key, $value) = explode('=', $option, 2);
      // Using Yaml::parse to ensure datatype is correct.
      $parameters[$key] = Yaml::parse($value);
    }

    // Generate the sandbox to execute the check.
    $sandbox = new Sandbox($targets[$target_name]->class, $policies[$policy]);
    $sandbox->setParameters($parameters)
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

    $io = new SymfonyStyle($input, $output);
    $io->title($response->getTitle());
    $io->text($response->getDescription());

    call_user_func([$io, $response->isSuccessful() ? 'success' : 'error'], $response->getSummary());

    if (!$response->isSuccessful()) {
      $io->title('Remediation');
      $io->text($response->getRemediation());
    }
  }

}
