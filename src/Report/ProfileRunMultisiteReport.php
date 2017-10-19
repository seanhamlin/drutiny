<?php

namespace Drutiny\Report;

use Drutiny\ProfileInformation;
use Drutiny\Target\Target;
use Drutiny\AuditResponse\AuditResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

/**
 *
 */
class ProfileRunMultisiteReport extends ProfileRunReport {

  /**
   * @inheritdoc
   */
  public function render(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $io->text('');

    // Set results by policy rather than by site.
    $resultsByPolicy = [];
    foreach ($this->resultSet as $uri => $siteReport) {
      foreach ($siteReport as $response) {
        $resultsByPolicy[$response->getName()][$uri] = $response;
      }
    }

    $table_rows = [];

    foreach ($resultsByPolicy as $policy => $results) {
      $failed = array_filter($results, function (AuditResponse $response) {
        return !$response->isSuccessful();
      });
      
      $pass = bcsub(count($results), count($failed));
      $pass_rate = round(bcdiv($pass, count($results)) * 100);

      $policyInfo = reset($results);
      $table_rows[] = [
        $policyInfo->getTitle(),
        $pass_rate . '% passed'
      ];
      $table_rows[] = [new TableCell($policyInfo->getDescription(), [
        'rowspan' => 2
        ])];

      foreach ($failed as $uri => $response) {
        $table_rows[] = [
          $uri,
          $response->getSummary(),
        ];
      }

      $table_rows[] = new TableSeparator();
    }

    // Remove last table seperator
    array_pop($table_rows);

    $io->title($this->info->get('title'));
    $io->table([], $table_rows);

    // $io->text("$total_pass/$total_tests Passed.");
  }

}
