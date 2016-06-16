<?php

namespace SiteAudit\Command;

use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Base\DrushCaller;
use SiteAudit\Context;
use SiteAudit\Executor\Executor;
use SiteAudit\Executor\ExecutorRemote;
use SiteAudit\Profile\Profile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class SiteAudit extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('audit:site')
      ->setDescription('Audit a Drupal site to ensure it meets best practice')
      ->addOption(
        'profile',
        'p',
        InputOption::VALUE_REQUIRED,
        'What site audit profile do you want to use?',
        'default'
      )
      ->addOption(
        'ssh_options',
        null,
        InputOption::VALUE_REQUIRED,
        'Passthrough any SSH options directly to SSH.',
        ''
      )
      ->addOption(
        'report-dir',
        'd',
        InputOption::VALUE_REQUIRED,
        'Set the location where the reports should be written to.',
        sys_get_temp_dir()
      )
      ->addArgument(
        'drush-alias',
        InputArgument::REQUIRED,
        'The drush alias for the site'
      )
    ;
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $time_start = microtime(true);
    $drush_alias = $input->getArgument('drush-alias');

    $reports_dir = $input->getOption('report-dir');
    if (!is_dir($reports_dir) || !is_writeable($reports_dir)) {
      throw new \RuntimeException("Cannot write to $reports_dir");
    }

    // Load the Drush alias which will contain more information we'll need.
    $executor = new Executor($output);
    $drush = new DrushCaller($executor);
    $response = $drush->siteAlias('@' . $drush_alias, '--format=json')->parseJson(TRUE);
    $alias = $response[$drush_alias];
    $drush->setAlias($drush_alias);

    $profile = new Profile();
    $profile->load($input->getOption('profile'));

    $context = new Context();
    $context->set('input', $input)
            ->set('output', $output)
            ->set('reportsDir', $reports_dir)
            ->set('profile', $profile)
            ->set('executor', $executor)
            ->set('remoteExecutor', $executor)
            ->set('drush', $drush)
            ->set('alias', $drush_alias)
            ->set('config', $alias);

    // Some checks don't use drush and connect to the server
    // directly so we need a remote executor available as well.
    if (isset($alias['remote-host'], $alias['remote-user'])) {
      $executor = new ExecutorRemote($output);
      $executor->setRemoteUser($alias['remote-user'])
               ->setRemoteHost($alias['remote-host']);
      if (isset($alias['ssh-options'])) {
        $executor->setArgument($alias['ssh-options']);
      }
      try {
        $executor->setArgument($input->getOption('ssh_options'));
      }
      catch (InvalidArgumentException $e) {}
      $context->set('remoteExecutor', $executor);
    }

    $results = $this->runChecks($context);
    $site['domain'] = $alias['uri'];
    $site['results'] = $results;
    $passes = [];
    $warnings = [];
    $failures = [];
    foreach ($results as $result) {
      if (in_array($result->getStatus(), [AuditResponse::AUDIT_SUCCESS, AuditResponse::AUDIT_NA], TRUE)) {
        $passes[] = (string) $result;
      }
      else if ($result->getStatus() === AuditResponse::AUDIT_WARNING) {
        $warnings[] = (string) $result;
      }
      else {
        $failures[] = (string) $result;
      }
    }
    $site['pass'] = count($passes);
    $site['warn'] = count($warnings);
    $site['fail'] = count($failures);

    // Optional report.
    if ($input->getOption('report-dir')) {
      $this->writeReport($reports_dir, $output, $profile, $site);
    }

    $time_end = microtime(true);
    $execution_time = date("H:i:s",$time_end - $time_start);
    $output->writeln('<info>Execution time: ' . $execution_time . '</info>');
  }

  protected function runChecks($context) {
    $results = [];
    foreach ($context->profile->getChecks() as $check => $options) {
      $test = new $check($context, $options);
      $result = $test->execute();
      $results[] = $result;
      $context->output->writeln(strip_tags((string) $result, '<info><comment><error>'));
    }
    return $results;
  }

  protected function writeReport($reports_dir, OutputInterface $output, $profile, Array $site) {
    ob_start();
    include dirname(__FILE__) . '/report/report-site.tpl.php';
    $report_output = ob_get_contents();
    ob_end_clean();

    $filename = implode('.', [$site['domain'], 'html']);
    $filepath = $reports_dir . '/' . $filename;

    if (is_file($filepath) && !is_writeable($filepath)) {
      throw new \RuntimeException("Cannot overwrite file: $filepath");
    }

    file_put_contents($filepath, $report_output);
    $output->writeln("<info>Report written to $filepath</info>");
  }

}
