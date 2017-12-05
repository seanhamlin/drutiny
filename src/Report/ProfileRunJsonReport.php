<?php

namespace Drutiny\Report;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class ProfileRunJsonReport extends ProfileRunReport {

  /**
   * @inheritdoc
   */
  public function render(InputInterface $input, OutputInterface $output) {
    $render_vars = $this->getRenderVariables();
    $content = json_encode($render_vars);

    $filename = $input->getOption('report-filename');
    if ($filename == 'stdout') {
      echo $content;
      return;
    }
    if (file_put_contents($filename, json_encode($render_vars, \JSON_PRETTY_PRINT))) {
      $output->writeln('<info>Report written to ' . $filename . '</info>');
    }
    else {
      echo $content;
      $ouput->writeln('<error>Could not write to ' . $filename . '. Output to stdout instead.</error>');
    }
  }

  /**
   * Form a render array as variables for rendering.
   */
  protected function getRenderVariables() {
    $render_vars = [
      'notices' => 0,
      'warnings' => 0,
      'failures' => 0,
      'passes' => 0,
      'errors' => 0,
      'not_applicable' => 0,
      'remediated' => 0,
      'total' => 0,
    ];

    // Report Title.
    $render_vars['title'] = $this->info->get('title');

    // Site domain.
    $render_vars['domain'] = $this->target->uri();

    $render_vars['summary'] = $this->target->uri();

    // Profile Description
    // $render_vars['description'] = $converter->convertToHtml(
    //   $this->info->get('description')
    // );.
    foreach ($this->resultSet as $response) {
      $var = [
        'status' => $response->isSuccessful(),
        'is_notice' => $response->isNotice(),
        'has_warning' => $response->hasWarning(),
        'has_error' => $response->hasError(),
        'is_not_applicable' => $response->isNotApplicable(),
        'title' => $response->getTitle(),
        'description' => $response->getDescription(),
        'remediation' => $response->getRemediation(),
        'success' => $response->getSuccess(),
        'failure' => $response->getFailure(),
        'warning' => $response->getWarning(),
      ];

      $render_vars['total']++;

      if ($response->isSuccessful()) {
        if ($response->isNotice()) {
          $render_vars['notices']++;
          $var['status_title'] = 'Notice';
        }
        elseif ($response->isRemediated()) {
          $render_vars['remediated']++;
          $var['status_title'] = 'Remediated';
        }
        else {
          $render_vars['passes']++;
          $var['status_title'] = 'Passed';
        }
      }
      elseif ($response->isNotApplicable()) {
        $render_vars['not_applicable']++;
        $var['status_title'] = 'Not Applicable';
      }
      else {
        if ($response->hasError()) {
          $render_vars['errors']++;
          $var['status_title'] = 'Error';
        }
        else {
          $render_vars['failures']++;
          $var['status_title'] = 'Failed';
        }
      }

      if ($response->hasWarning()) {
        $render_vars['warnings']++;
        $var['status_title'] .= ' with warning';
      }

      $render_vars['results'][] = $var;
    }
    return $render_vars;
  }

}
