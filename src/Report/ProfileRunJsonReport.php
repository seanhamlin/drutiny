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
    usort($this->resultSet, [$this, 'usort']);
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
    $outcomes = [
      'success' => 0,
      'failure' => 0,
      'warning' => 0,
      'error' => 0,
      'not_applicable' => 0,
    ];
    $render_vars['stats'] = [
      'critical' => $outcomes,
      'high' => $outcomes,
      'normal' => $outcomes,
      'low' => $outcomes,
      'none' => $outcomes,
    ];

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
          $render_vars['stats'][$var['severity']]['error']++;
          break;

        case 'not-applicable':
          $render_vars['not_applicable']++;
          $var['status_title'] = 'Not Applicable';
          $render_vars['stats'][$var['severity']]['not_applicable']++;
          break;

        case 'warning':
          $render_vars['warnings']++;
          $var['status_title'] = 'Warning';
          $render_vars['stats'][$var['severity']]['warning']++;
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
          $render_vars['stats'][$var['severity']]['success']++;
          break;

        case 'failure':
          $render_vars['failures']++;
          $var['status_title'] = 'Failed';
          $render_vars['remediations'][] = $response->getRemediation();
          $render_vars['stats'][$var['severity']]['failure']++;
          break;
      }
      $render_vars['results'][] = $var;
    }

    $render_vars['stats'] = array_filter($render_vars['stats'], function ($a) {
      return count(array_filter($a));
    });

    foreach ($render_vars['stats'] as $severity => $results) {
      $render_vars['totals'][$severity] = array_sum($results);
    }

    return $render_vars;
  }

}
