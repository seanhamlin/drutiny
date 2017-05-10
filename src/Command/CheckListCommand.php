<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
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
      ->setDescription('Show all checks available.')
      ->addOption(
        'filter',
        't',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Filter list by tag'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $checks = Registry::checks();

    $filters = $input->getOption('filter');

    $rows = array();
    foreach ($checks as $name => $info) {
      // If there are filters present, only show checks that match those filters.
      foreach ($filters as $filter) {
        if (!$info->hasTag($filter)) {
          continue 2;
        }
      }

      // Skip over testing checks.
      // TODO: Implement testing checks.
      // if ($info->testing) {
      //   continue;
      // }.
      $rows[] = array(
        'name' => $name,
        'description' => implode(PHP_EOL, [
          '<options=bold>' . wordwrap($info->get('title'), 50) . '</>',
        //  $this->formatDescription($info->get('description')),
        //  NULL,
        ]),
        'supports_remediation' => $info->get('remediable') ? 'Yes' : 'No',
        'tags' => implode(', ', $info->getTags()),
      );
      // $rows[] = new TableSeparator();
    }

    $table = new Table($output);
    $table
      ->setHeaders(array('Name', 'Title', 'Self-heal', 'Tags'))
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
