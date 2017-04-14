<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drutiny\Registry;

/**
 *
 */
class CheckListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('check:list')
      ->setDescription('Show all checks available.');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $checks = Registry::checks();

    $rows = array();
    foreach ($checks as $name => $info) {
      // Skip over testing checks.
      // TODO: Implement testing checks.
      // if ($info->testing) {
      //   continue;
      // }.
      $rows[] = array(
        'name' => $name,
        'description' => implode(PHP_EOL, [
          '<options=bold>' . wordwrap($info->get('title'), 50) . '</>',
          $this->formatDescription($info->get('description')),
          NULL,
        ]),
        'supports_remediation' => $info->get('remediable') ? 'Yes' : 'No',
      );
      // $rows[] = new TableSeparator();
    }

    $table = new Table($output);
    $table
      ->setHeaders(array('Name', 'Description', 'Self-heal'))
      ->setRows($rows)
      ->getStyle()
      ->setVerticalBorderChar(' ')
      ->setHorizontalBorderChar(' ')
      ->setCrossingChar('');

    $table->render();
  }

  /**
   *
   */
  protected function formatDescription($text) {
    $lines = explode(PHP_EOL, $text);
    $text = implode(' ', $lines);
    return wordwrap($text, 50);
  }

}
