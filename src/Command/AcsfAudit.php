<?php

namespace SiteAudit\Command;

use SiteAudit\Base\DrushCaller;
use SiteAudit\Context;
use SiteAudit\Profile\Profile;
use SiteAudit\Executor\Executor;
use SiteAudit\Executor\ExecutorRemote;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use SiteAudit\AuditResponse\AuditResponse;

class AcsfAudit extends SiteAudit {

  /**
   * @inheritdoc
   */
  protected function configure() {
    parent::configure();

    $this
      ->setName('audit:acsf')
      ->setDescription('Audit all sites on a Site Factory instance')
      ->addOption(
        'www-only',
        FALSE,
        InputOption::VALUE_NONE,
        'If set, only sites with www in the URL will be tested'
      );
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
    // $drush->setAlias($drush_alias);

    // We need to obtain the sites.json file from the ACSF server.
    $docroot_parts = explode('/', $alias['root']);
    array_pop($docroot_parts);
    $docroot_name = array_pop($docroot_parts);
    $acsf_sites_json = '/mnt/files/' . $docroot_name . '/files-private/sites.json';

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
    }

    $json_data = $executor->execute('cat ' . $acsf_sites_json)->parseJson(TRUE);
    $sites = $json_data['sites'];

    $profile = new Profile();
    $profile->load($input->getOption('profile'));

    // The parsed json can contain multiple duplicate site entries due to
    // site collections and site clones. We want to ensure we do not run the
    // checks over the site collections, but much rather the individual sites.
    // If possible we prefer sites that start with the www prefix.
    $unique_sites = [];
    foreach ($sites as $domain => $site) {
      $www_only = $input->getOption('www-only');
      $is_www = strpos($domain, 'www.') === 0;
      $is_acsf_domain = strpos($domain, 'acsitefactory.com') !== FALSE;

      // e.g. 'ogq621' or 'net126'.
      if (array_key_exists($site['name'], $unique_sites)) {
        if ($is_www) {
          $unique_sites[$site['name']] = ['domain' => $domain];
        }
        // Not www but still a custom domain, only add it if there is a www in
        // it.
        else if (!$is_acsf_domain && strpos($unique_sites[$site['name']]['domain'], 'www.') !== 0 && !$www_only) {
          $unique_sites[$site['name']] = ['domain' => $domain];
        }
        else {
          // This domain is not better, so skip it.
          continue;
        }
      }

      // This is the first time we have seen this domain.
      else {
        if ($is_www || !$www_only) {
          $unique_sites[$site['name']] = ['domain' => $domain];
        }
      }
    }

    //var_dump($unique_sites);
    //$unique_sites = array_slice($unique_sites, 0 , 5, TRUE);
    //$unique_sites = array_slice($unique_sites, 185 , 8, TRUE);

    $output->writeln('<comment>Found ' . count($unique_sites) . ' unqiue sites</comment>');

    $i = 0;
    foreach ($unique_sites as $id => $values) {
      $i++;
      $domain = $values['domain'];
      $drush = new DrushCaller($executor);
      $drush->setArgument('--uri=' . $domain)
            ->setArgument('--root=' . $alias['root']);

      $context = new Context();
      $context->set('input', $input)
              ->set('output', $output)
              ->set('reportsDir', $reports_dir)
              ->set('executor', $executor)
              ->set('profile', $profile)
              ->set('remoteExecutor', $executor)
              ->set('drush', $drush);

      $output->writeln("<comment>[$i] Running audit over: {$domain}</comment>");
      $results = $this->runChecks($context);
      $unique_sites[$id]['results'] = $results;
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
      $unique_sites[$id]['pass'] = count($passes);
      $unique_sites[$id]['warn'] = count($warnings);
      $unique_sites[$id]['fail'] = count($failures);
      $output->writeln('<info>' . count($passes) . '/' . count($results) . ' tests passed.</info>');
      foreach ($warnings as $warning) {
        $context->output->writeln("\t" . strip_tags($warning, '<info><comment><error>'));
      }
      foreach ($failures as $fail) {
        $context->output->writeln("\t" . strip_tags($fail, '<info><comment><error>'));
      }
      $context->output->writeln('----');
    }

    // Optional report.
    if ($input->getOption('report-dir')) {
      uasort($unique_sites, function ($a, $b) {
        if ($a['pass'] == $b['pass']) {
          if ($a['warn'] == $b['warn']) {
            return 0;
          }
          return ($a['warn'] < $b['warn']) ? -1 : 1;
        }
        return ($a['pass'] < $b['pass']) ? -1 : 1;
      });
      $this->writeReport($reports_dir, $output, $profile, $unique_sites);
    }

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start)/60;
    $output->writeln('<info>Execution time: ' . $execution_time . '</info>');
  }

  protected function runChecks($context) {
    $results = [];
    foreach ($context->profile->getChecks() as $check => $options) {
      $test = new $check($context, $options);
      $results[] = $test->execute();
    }
    return $results;
  }

  protected function writeReport($reports_dir, OutputInterface $output, $profile, Array $unique_sites) {
    ob_start();
    include dirname(__FILE__) . '/report/report-acsf.tpl.php';
    $report_output = ob_get_contents();
    ob_end_clean();

    $filename = implode('.', [$profile->getMachineName(), 'html']);
    $filepath = $reports_dir . '/' . $filename;

    if (is_file($filepath) && !is_writeable($filepath)) {
      throw new \RuntimeException("Cannot overwrite file: $filepath");
    }

    file_put_contents($filepath, $report_output);
    $output->writeln("<info>Report written to $filepath</info>");
  }

}
