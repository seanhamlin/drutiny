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
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class CheckRunCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('check:run')
      ->setDescription('Run a single check')
      ->addArgument(
        'check',
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
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Set parameters for the check.',
        []
      )
      ->addOption(
        'remediate',
        'r',
        InputOption::VALUE_NONE,
        'Allow failed checks to remediate themselves if available.'
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
    $check_name = $input->getArgument('check');
    $checks = Registry::checks();
    if (!isset($checks[$check_name])) {
      throw new InvalidArgumentException("$check_name is not a valid check.");
    }

    // Setup any parameters for the check.
    $parameters = [];
    foreach ($input->getOption('set-parameter') as $option) {
      list($key, $value) = explode('=', $option, 2);
      // Using Yaml::parse to ensure datatype is correct.
      $parameters[$key] = Yaml::parse($value);
    }

    // Generate the sandbox to execute the check.
    $sandbox = new Sandbox($targets[$target_name]->class, $checks[$check_name]);
    $sandbox->setParameters($parameters)
      ->setLogger(new ConsoleLogger($output))
      ->getTarget()
      ->parse($target_data);

    $response = $sandbox->run();

    // Attempt remeidation.
    if (!$response->isSuccessful() && $input->getOption('remediate')) {
      $response = $sandbox->remediate();
    }

    $io = new SymfonyStyle($input, $output);
    $io->title($response->getTitle());
    $io->text($response->getDescription());

    call_user_func([$io, $response->isSuccessful() ? 'success' : 'error'], $response->getSummary());
  }

}
