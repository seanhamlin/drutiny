<?php

namespace Drutiny\Report;

use Drutiny\ProfileInformation;
use Drutiny\Target\Target;
use Drutiny\Registry;
use Drutiny\AuditResponse\AuditResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *
 */
class ProfileRunMultisiteHTMLReport extends ProfileRunReport {

  /**
   * @inheritdoc
   */
  public function render(InputInterface $input, OutputInterface $output) {
    // Check YAML supports markdown and needs to be converted into HTML before
    // we pass it into our report template.
    $parsedown = new \Parsedown();

    // Set results by policy rather than by site.
    $vars = [];
    foreach ($this->resultSet as $uri => $siteReport) {
      $vars['domains'][] = $uri;
      foreach ($siteReport as $response) {
        $vars['results'][$response->getName()]['sites'][$uri] = [
          'status' => $response->isSuccessful(),
          'is_notice' => $response->isNotice(),
          'has_warning' => $response->hasWarning(),
          'title' => $response->getTitle(),
          'description' =>
            $parsedown->text($response->getDescription()),
          'remediation' =>
            $parsedown->text($response->getRemediation()),
          'success' =>
            $parsedown->text($response->getSuccess()),
          'failure' =>
            $parsedown->text($response->getFailure()),
          'warning' =>
            $parsedown->text($response->getWarning()),
          'summary' =>
            $parsedown->text($response->getSummary()),
        ];
      }
    }

    foreach ($vars['results'] as $name => &$policy_results) {
      $policy_results['pass'] = 0;
      $policy_results['fail'] = 0;
      $policy_results['total'] = count($policy_results['sites']);
      $info = reset($policy_results['sites']);
      $policy_results['description'] = $info['description'];
      $policy_results['title'] = $info['title'];
      $policy_results['id'] = preg_replace('/[^0-9a-zA-Z]/', '', $name);

      foreach ($policy_results['sites'] as $site) {
        if ($site['status']) {
          $policy_results['pass']++;
        }
        else {
          $policy_results['fail']++;
        }
      }
    }

    $tpl = $this->info->get('template');
    $tpl = $tpl == 'site' ? 'multisite' : $tpl;

    $vars['title'] = $this->info->get('title');

    $content = $this->renderTemplate($tpl, $vars);

    $filename = $input->getOption('report-filename');
    if ($filename == 'stdout') {
      echo $content;
      return;
    }
    if (file_put_contents($filename, $content)) {
      $output->writeln('<info>Report written to ' . $filename . '</info>');
    }
    else {
      echo $content;
      $ouput->writeln('<error>Could not write to ' . $filename . '. Output to stdout instead.</error>');
    }
  }

  /**
   * Render an HTML template.
   *
   * @param string $tpl
   *   The name of the .html.tpl template file to load for rendering.
   * @param array $render_vars
   *   An array of variables to be used within the template by the rendering engine.
   */
  public function renderTemplate($tpl, array $render_vars) {
    $registry = new Registry();
    $loader = new \Twig_Loader_Filesystem($registry->templateDirs());
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/drutiny/cache',
      'auto_reload' => TRUE,
    ));
    // $filter = new \Twig_SimpleFilter('filterXssAdmin', [$this, 'filterXssAdmin'], [
    //   'is_safe' => ['html'],
    // ]);
    // $twig->addFilter($filter);
    $template = $twig->load($tpl . '.html.twig');
    $contents = $template->render($render_vars);
    return $contents;
  }

}
