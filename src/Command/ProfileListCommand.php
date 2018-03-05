<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Drutiny\Registry;

/**
 *
 */
class ProfileListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('profile:list')
      ->setDescription('Show all profiles available.');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $profiles = (new Registry())->profiles();

    $rows = array();
    foreach ($profiles as $name => $info) {
      $checks = array_keys($info->getPolicies());
      $checks = implode(', ', $checks);
      $checks = wordwrap($checks, 80);
      $rows[] = [
        $name,
        '<options=bold>' . wordwrap($info->get('title'), 50) . '</>',
        $checks,
      ];
      $rows[] = new TableSeparator();
    }

    $table = new Table($output);
    $table
      ->setHeaders(array('Name', 'Title', 'Checks'))
      ->setRows($rows)
      ->getStyle()
      ->setVerticalBorderChar(' ')
      ->setHorizontalBorderChar(' ')
      ->setCrossingChar('');

    $table->render();
  }

}
