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
      $output->writeln('<error>Could not write to ' . $filename . '. Output to stdout instead.</error>');
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

    $render_vars['description'] = $this->info->get('description');

    $render_vars['remediations'] = [];

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
        'type' => $response->getType(),
        'severity' => $response->getSeverity(),
      ];

      $render_vars['total']++;

      switch ($response->getType()) {
        case 'data':
        case 'notice':
          $render_vars['notices']++;
          $var['status_title'] = 'Notice';
          break;

        case 'error':
          $render_vars['errors']++;
          $var['status_title'] = 'Error';
          break;

        case 'not-applicable':
          $render_vars['not_applicable']++;
          $var['status_title'] = 'Not Applicable';
          break;

        case 'warning':
          $render_vars['warnings']++;
          $var['status_title'] = 'Warning';
          break;

        case 'success':
          $render_vars['passes']++;
          if ($response->isRemediated()) {
            $render_vars['remediated']++;
            $var['status_title'] = 'Remediated';
          }
          else {
            $var['status_title'] = 'Passed';
          }
          break;

        case 'failure':
          $render_vars['failures']++;
          $var['status_title'] = 'Failed';
          $render_vars['remediations'][] = $response->getRemediation();
          break;
      }

      $render_vars['results'][] = $var;
    }
    return $render_vars;
  }

}
