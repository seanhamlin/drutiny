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
    $render_vars = [];

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
      $render_vars['results'][] = [
        'status' => $response->isSuccessful(),
        'is_notice' => $response->isNotice(),
        'has_warning' => $response->hasWarning(),
        'title' => $response->getTitle(),
        'description' => $response->getDescription(),
        'remediation' => $response->getRemediation(),
        'success' => $response->getSuccess(),
        'failure' => $response->getFailure(),
        'warning' => $response->getWarning(),
      ];
    }
    return $render_vars;
  }

}
