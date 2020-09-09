<?php

namespace Drutiny\Command;

use Drutiny\Assessment;
use Drutiny\Container;
use Drutiny\Profile;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\ProgressBar;
use Drutiny\RemediableInterface;
use Drutiny\Report\Format;
use Drutiny\Report\ProfileRunReport;
use Drutiny\Target\Registry as TargetRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class PolicyAuditCommand extends AbstractReportingCommand {

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
        'root',
        null,
        InputOption::VALUE_OPTIONAL,
        'Root path for the Drush alias.'
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
        'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.',
        'default'
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
      );
      parent::configure();
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Ensure Container logger uses the same verbosity.
    Container::setVerbosity($output->getVerbosity());

    // Setup any parameters for the check.
    $parameters = [];
    foreach ($input->getOption('set-parameter') as $option) {
      list($key, $value) = explode('=', $option, 2);
      // Using Yaml::parse to ensure datatype is correct.
      $parameters[$key] = Yaml::parse($value);
    }

    $name = $input->getArgument('policy');
    $profile = new Profile();
    $profile->setTitle('Policy Audit: ' . $name)
            ->setName($name)
            ->setFilepath('/dev/null')
            ->addPolicyDefinition(
              PolicyDefinition::createFromProfile($name, 0, [
                'parameters' => $parameters
              ])
            )
            ->addFormatOptions(Format::create('terminal', [
              'content' => Yaml::parseFile(dirname(__DIR__) . '/Report/templates/content/policy.markdown.yml')
            ]));

    // Setup the target.
    $target = TargetRegistry::loadTarget($input->getArgument('target'));
    $target->setUri($input->getOption('uri'));
    $target->setRoot($input->getOption('root'));

    $assessment = new Assessment($input->getOption('uri'));
    $result = [];

    $start = new \DateTime($input->getOption('reporting-period-start'));
    $end   = new \DateTime($input->getOption('reporting-period-end'));
    $profile->setReportingPeriod($start, $end);

    $policies = [];
    foreach ($profile->getAllPolicyDefinitions() as $definition) {
      $policies[] = $definition->getPolicy();
    }

    $assessment->assessTarget($target, $policies, $start, $end, $input->getOption('remediate'));

    if (!$input->getOption('report-filename')) {
      $input->setOption('report-filename', 'stdout');
    }

    $this->report($profile, $input, $output, $target, [$assessment]);
  }
}
