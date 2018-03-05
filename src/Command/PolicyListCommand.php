<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\Registry;

/**
 *
 */
class PolicyListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('policy:list')
      ->setDescription('Show all policies available.')
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
    $checks = (new Registry)->policies();

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
        'description' => implode(PHP_EOL, [
          '<options=bold>' . wordwrap($info->get('title'), 50) . '</>',
        //  $this->formatDescription($info->get('description')),
        //  NULL,
        ]),
        'name' => $name,
        'supports_remediation' => $info->get('remediable') ? 'Yes' : 'No',
        'tags' => implode(', ', $info->getTags()),
      );
      // $rows[] = new TableSeparator();
    }

    usort($rows, function ($a, $b) {
      $x = [strtolower($a['name']), strtolower($b['name'])];
      sort($x, SORT_STRING);

      return $x[0] == strtolower($a['name']) ? -1 : 1;
    });

    $io = new SymfonyStyle($input, $output);
    $io->table(['Title', 'Name', 'Self-heal', 'Tags'], $rows);
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
