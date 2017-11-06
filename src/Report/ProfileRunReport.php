<?php

namespace Drutiny\Report;

use Drutiny\ProfileInformation;
use Drutiny\Target\Target;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 *
 */
class ProfileRunReport implements ProfileRunReportInterface {

  const EMOJI_PASS = "\xe2\x9c\x85";

  const EMOJI_FAIL = "\xE2\x9D\x8C";

  /**
   * @var Drutiny\ProfileInformation
   */
  protected $info;

  /**
   * @var Drutiny\Target\Target
   */
  protected $target;

  /**
   * @var array
   */
  protected $resultSet;

  /**
   * @inheritdoc
   */
  public function __construct(ProfileInformation $info, Target $target, array $result_set) {
    $this->info = $info;
    $this->resultSet = $result_set;
    $this->target = $target;
  }

  /**
   * @inheritdoc
   */
  public function render(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $io->title($this->info->get('title'));

    $table_rows = [];
    $pass = [];
    foreach ($this->resultSet as $response) {
      $pass[] = $response->isSuccessful();
      $table_rows[] = [
        $response->isSuccessful() ? self::EMOJI_PASS : self::EMOJI_FAIL,
        $response->getTitle(),
        $response->getSummary() . (
          $response->isSuccessful() ? '' : PHP_EOL . PHP_EOL . $response->getRemediation()
        ),
      ];
      $table_rows[] = new TableSeparator();
    }

    $total_tests = count($this->resultSet);
    $total_pass = count(array_filter($pass));
    $table_rows[] = ['', "$total_pass/$total_tests passed", ''];
    $io->table(['', 'Policy', 'Summary'], $table_rows);
  }

}
