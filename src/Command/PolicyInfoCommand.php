<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\Registry;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 *
 */
class PolicyInfoCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('policy:info')
      ->setDescription('Show information about a specific policy.')
      ->addArgument(
        'policy',
        InputArgument::REQUIRED,
        'The name of the check to run.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $checks = (new Registry)->policies();

    $check_name = $input->getArgument('policy');
    if (!isset($checks[$check_name])) {
      throw new InvalidArgumentException("$check is not a valid policy.");
    }

    $info = $checks[$check_name];

    $rows = array();
    $rows[] = ['Check', $info->get('title')];
    $rows[] = new TableSeparator();
    $rows[] = ['Description', $this->formatDescription($info->get('description'))];
    $rows[] = new TableSeparator();
    $rows[] = ['Remediable', $info->get('remediable') ? 'Yes' : 'No'];
    $rows[] = new TableSeparator();
    $rows[] = ['Parameters', $this->formatParameters($info->get('parameters'))];

    $io = new SymfonyStyle($input, $output);
    $io->table([], $rows);
  }

  /**
   *
   */
  protected function formatDescription($text) {
    $lines = explode(PHP_EOL, $text);
    $text = implode(' ', $lines);
    return wordwrap($text, 80);
  }

  /**
   *
   */
  protected function formatParameters($parameters) {
    $output = [];
    foreach ($parameters as $key => $info) {
      $output[] = $key . ':' . $info['type'];
      $lines = explode(PHP_EOL, $info['description']);
      $lines = array_map(function ($line) {
        return "  " . $line;
      }, $lines);
      $output[] = implode(PHP_EOL, $lines);
    }
    if (empty($output)) {
      return '(none)';
    }
    return implode(PHP_EOL, $output);
  }

}
