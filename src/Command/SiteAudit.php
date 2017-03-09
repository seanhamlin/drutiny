<?php

namespace Drutiny\Command;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Base\DrushCaller;
use Drutiny\Base\PhantomasCaller;
use Drutiny\Settings\SettingsCheck;
use Drutiny\Context;
use Drutiny\Executor\Executor;
use Drutiny\Executor\ExecutorRemote;
use Drutiny\Profile\ProfileController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class SiteAudit extends Command {

  protected $start = NULL;
  protected $end = NULL;

  // Keeps track on whether this is a local or remote site audit.
  protected $isRemote = FALSE;

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
        'What drutiny profile do you want to use?',
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
      ->addOption(
        'drush-bin',
        'b',
        InputOption::VALUE_REQUIRED,
        'Set the alias to use for drush.',
        'drush'
      )
      ->addOption(
        'auto-remediate',
        'r',
        InputOption::VALUE_NONE,
        'If set, certain checks will auto-remediate and issue write commands back to the Drupal sites.'
      )
      ->addArgument(
        'drush-alias',
        InputArgument::REQUIRED,
        'The drush alias for the site you wish to audit.'
      )
    ;
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->timerStart();

    $io = new SymfonyStyle($input, $output);
    $io->title('Drutiny site audit');

    // Normalise the @ in the alias. Remove it to be safe.
    $drush_alias = str_replace('@', '', $input->getArgument('drush-alias'));

    // Validate the reports directory.
    $reports_dir = $input->getOption('report-dir');
    if (!is_dir($reports_dir) || !is_writeable($reports_dir)) {
      throw new \RuntimeException("Cannot write to $reports_dir");
    }

    // Validate the drush binary.
    $drush_bin = $input->getOption('drush-bin');
    if (!$this->command_exist($drush_bin)) {
      throw new \RuntimeException("No drush binary available called '$drush_bin'.");
    }

    // Load the Drush alias which will contain more information we'll need.
    $executor = new Executor($io);
    $drush = new DrushCaller($executor, $input->getOption('drush-bin'));
    $phantomas = new PhantomasCaller($executor, $drush);
    $response = $drush->siteAlias('@' . $drush_alias, '--format=json')->parseJson(TRUE);

    // Check for made up aliases.
    if (!array_key_exists($drush_alias, $response)) {
      throw new \Exception('Missing site alias for ' . $drush_alias . '. Please check `drush sa` for a list of aliases.');
    }

    $alias = $response[$drush_alias];
    $drush->setAlias($drush_alias);

    $phantomas->setDomain($alias['uri']);

    $profile = ProfileController::load($input->getOption('profile'));

    $context = new Context();
    $context->set('input', $input)
            ->set('output', $output)
            ->set('io', $io)
            ->set('reportsDir', $reports_dir)
            ->set('profile', $profile)
            ->set('executor', $executor)
            ->set('remoteExecutor', $executor)
            ->set('drush', $drush)
            ->set('phantomas', $phantomas)
            ->set('alias', $drush_alias)
            ->set('config', $alias)
            ->set('autoRemediate', $input->getOption('auto-remediate'));

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
      catch (InvalidArgumentException $e) {}
      $context->set('remoteExecutor', $executor);
      $this->isRemote = TRUE;
      $drush->setIsRemote($this->isRemote);
      $context->set('drush', $drush);
    }

    $results = $this->runChecks($context, TRUE);
    $results = array_merge($results, $this->runSettings($context));

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

    // Optional HTML report.
    if ($input->getOption('report-dir')) {
      $this->ensureTimezoneSet();
      $this->writeHTMLReport('site', $reports_dir, $io, $profile, $site);
    }

    $io->text('Execution time: ' . $this->timerEnd() . ' seconds');
  }

  /**
   * Check to see if a given command exists in the source system.
   *
   * @param  String $cmd
   *   The command you want to see if it exists.
   * @return bool
   *   Whether or not a particular command exists.
   */
  function command_exist($cmd) {
    $return_val = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
    return !empty($return_val);
  }

  protected function timerStart() {
    $this->start = microtime(true);
  }

  protected function timerEnd() {
    $this->end = microtime(true);
    return (int) ($this->end - $this->start);
  }

  /**
   * Perform the checks.
   *
   * @param $context
   *   The context of the check.
   * @param $print
   *   Whether the check should print to the CLI.
   * @return array
   *   Array of results.
   */
  protected function runChecks($context, $print = TRUE) {
    $results = [];
    foreach ($context->profile->getChecks() as $check => $options) {
      $test = new $check($context, $options);
      $result = $test->execute();
      $results[] = $result;
      if ($print) {
        $context->output->writeln(strip_tags((string) $result, '<info><comment><error>'));
      }
    }
    return $results;
  }

  /**
   * Perform settings checks for each module defined in the settings hash.
   *
   * @param $context
   *   The context of the check.
   * @param $print
   *   Whether the check should print to the CLI.
   * @return array
   *   Array of results.
   */
  protected function runSettings($context, $print = TRUE) {
    $results = [];
    foreach ($context->profile->getSettings() as $machine_name => $options) {
      $options['machine_name'] = $machine_name;
      $settings = new SettingsCheck($context, $options);
      $result = $settings->execute();
      $results[] = $result;
      if ($print) {
        $context->output->writeln(strip_tags((string) $result, '<info><comment><error>'));
      }
    }
    return $results;
  }

  /**
   * Ensure there is a timezone set, if there is not already one. Note that one
   * of the checks `CronHasRun` will set the timezone to be the site's timezone.
   * UTC is a fallback in this case.
   */
  protected function ensureTimezoneSet() {
    if (@date_default_timezone_get() === 'UTC') {
      date_default_timezone_set('UTC');
    }
  }

  /**
   * Convert the results into HTML.
   *
   * @param string $template
   * @param [type]          $reports_dir [description]
   * @param OutputInterface $output      [description]
   * @param Profile         $profile     [description]
   * @param Array           $site        [description]
   */
  protected function writeHTMLReport($template, $reports_dir, SymfonyStyle $io, $profile, Array $site, Array $sites = []) {
    $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../templates');
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/cache',
      'auto_reload' => true,
    ));
    $filter = new \Twig_SimpleFilter('filterXssAdmin', [$this, 'filterXssAdmin'], [
      'is_safe' => ['html'],
    ]);
    $twig->addFilter($filter);
    $template = $twig->load($template . '.html.twig');
    $contents = $template->render([
      'profile' => $profile,
      'site' => $site,
      'sites' => $sites,
    ]);

    $filename = 'drutiny.html';
    if (!empty($site)) {
      $filename = implode('.', [$site['domain'], 'html']);
    }
    elseif (!empty($sites)) {
      $filename = implode('.', [$profile->getMachineName(), 'html']);
    }
    $filepath = $reports_dir . '/' . $filename;

    if (is_file($filepath) && !is_writeable($filepath)) {
      throw new \RuntimeException("Cannot overwrite file: $filepath");
    }

    file_put_contents($filepath, $contents);
    $io->success("Report written to {$filepath}");
  }

  /**
   * Strip all HTML tags except certain safe tags.
   *
   * @param string $string
   *   The string to strip.
   * @return string
   *   The stripped string.
   */
  public static function filterXssAdmin($string) {
    return strip_tags($string, '<a><abbr><br><code><em><p><pre><span><strong><ul><ol><li>');
  }

}
