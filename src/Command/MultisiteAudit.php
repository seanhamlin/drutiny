<?php

namespace Drutiny\Command;

use Drutiny\Base\DrushCaller;
use Drutiny\Base\PhantomasCaller;
use Drutiny\Context;
use Drutiny\Executor\Executor;
use Drutiny\Executor\ExecutorRemote;
use Drutiny\Profile\ProfileController;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\AuditResponse\AuditResponse;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class MultisiteAudit extends SiteAudit {

  /**
   * @inheritdoc
   */
  protected function configure() {
    parent::configure();

    $this
      ->setName('audit:multisite')
      ->setDescription('Audit sites in a multisite instance')
      ->addOption(
        'domain-file',
        'f',
        InputOption::VALUE_REQUIRED,
        'A YAML file listing domains to audit',
        'domains.yml'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->timerStart();

    $io = new SymfonyStyle($input, $output);
    $io->title('Drutiny multisite audit');

    // Normalise the @ in the alias. Remove it to be safe.
    $drush_alias = str_replace('@', '', $input->getArgument('drush-alias'));

    // Validate the reports directory.
    $this->reportsDir = $input->getOption('report-dir');
    if (!is_dir($this->reportsDir) || !is_writable($this->reportsDir)) {
      throw new \RuntimeException("Cannot write to {$this->reportsDir}.");
    }

    // Validate the drush binary.
    $drush_bin = $input->getOption('drush-bin');
    if (!$this->commandExists($drush_bin)) {
      throw new \RuntimeException("No drush binary available called '$drush_bin'.");
    }

    // Load the Drush alias which will contain more information we'll need.
    $executor = new Executor($io);
    $drush = new DrushCaller($executor, $input->getOption('drush-bin'));
    $phantomas = new PhantomasCaller($executor, $drush);
    $response = $drush->siteAlias('@' . $drush_alias, '--format=json')->parseJson(TRUE);
    $alias = $response[$drush_alias];

    $profile = ProfileController::load($input->getOption('profile'));

    // Some checks don't use drush and connect to the server directly so we need
    // a remote executor available as well.
    if (isset($alias['remote-host'], $alias['remote-user'])) {
      $executor = new ExecutorRemote($io);
      $executor->setRemoteUser($alias['remote-user'])
        ->setRemoteHost($alias['remote-host']);
      if (isset($alias['ssh-options'])) {
        $executor->setArgument($alias['ssh-options']);
      }
      try {
        $executor->setArgument($input->getOption('ssh_options'));
      }
      catch (InvalidArgumentException $e) {
      }
      $this->isRemote = TRUE;
    }

    $context = new Context();
    $context->set('input', $input)
      ->set('output', $output)
      ->set('io', $io)
      ->set('reportsDir', $this->reportsDir)
      ->set('executor', $executor)
      ->set('profile', $profile)
      ->set('remoteExecutor', $executor)
      ->set('drush', $drush)
      ->set('phantomas', $phantomas)
      ->set('autoRemediate', $input->getOption('auto-remediate'));

    $yaml = file_get_contents($input->getOption('domain-file'));
    $domains = Yaml::parse($yaml);
    $domains = $domains['domains'];

    $unique_sites = [];
    foreach ($domains as $domain) {
      $unique_sites[$domain] = ['domain' => $domain];
    }

    $io->comment('Found ' . count($unique_sites) . ' unique sites.');

    $i = 0;
    foreach ($unique_sites as $id => $values) {
      $i++;
      $domain = $values['domain'];
      $drush = new DrushCaller($executor, $input->getOption('drush-bin'));
      $drush->setArgument('--uri=' . $domain)
        ->setArgument('--root=' . $alias['root'])
        ->setIsRemote($this->isRemote)
        ->setSingleSite(FALSE);

      $context->set('drush', $drush);

      $phantomas->setDomain($domain)
        ->setDrush($drush)
        ->clearMetrics();
      $context->set('phantomas', $phantomas);

      $io->comment("[{$i}] Running audit over: {$domain}");
      $results = $this->runChecks($context, FALSE);
      $results = array_merge($results, $this->runSettings($context, FALSE));
      $unique_sites[$id]['results'] = $results;
      $passes = [];
      $warnings = [];
      $failures = [];
      foreach ($results as $result) {
        if (in_array($result->getStatus(), [AuditResponse::AUDIT_SUCCESS, AuditResponse::AUDIT_NA], TRUE)) {
          $passes[] = (string) $result;
        }
        elseif ($result->getStatus() === AuditResponse::AUDIT_WARNING) {
          $warnings[] = (string) $result;
        }
        else {
          $failures[] = (string) $result;
        }
      }
      $unique_sites[$id]['pass'] = count($passes);
      $unique_sites[$id]['warn'] = count($warnings);
      $unique_sites[$id]['fail'] = count($failures);
      $io->writeln('<info>' . count($passes) . '/' . count($results) . ' tests passed.</info>');
      foreach ($warnings as $warning) {
        $io->writeln("\t" . strip_tags($warning, '<info><comment><error>'));
      }
      foreach ($failures as $fail) {
        $io->writeln("\t" . strip_tags($fail, '<info><comment><error>'));
      }
      $io->writeln('----');
    }

    // Sort the sites by worst offending at the top.
    // @TODO make this a function.
    uasort($unique_sites, function ($a, $b) {
      if ($a['pass'] == $b['pass']) {
        if ($a['warn'] == $b['warn']) {
          return 0;
        }
        return ($a['warn'] < $b['warn']) ? -1 : 1;
      }
      return ($a['pass'] < $b['pass']) ? -1 : 1;
    });

    // Output the report in the desired format, can be multiple.
    foreach ($input->getOption('format') as $format) {
      $filepath = $this->getReportFilepath($profile, $format, [], $unique_sites);
      switch ($format) {
        case 'json':
          $json = json_encode($unique_sites, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
          file_put_contents($filepath, $json);
          $io->success("JSON report written to {$filepath}");
          break;

        case 'html':
          $this->ensureTimezoneSet();
          $html = $this->getHTMLReport('multisite', $io, $profile, [], $unique_sites);
          file_put_contents($filepath, $html);
          $io->success("HTML report written to {$filepath}");
          break;

        default:
          throw new \Exception("Invalid output format {$format}. Supported formats are 'html' and 'json'.");
      }
    }

    $io->text("Execution time: {$this->timerEnd()} seconds.");
  }

}
