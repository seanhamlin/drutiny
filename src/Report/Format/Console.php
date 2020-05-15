<?php

namespace Drutiny\Report\Format;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Assessment;
use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\Target\Target;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class Console extends Format {

  public function __construct($options) {
    parent::__construct($options);
    $this->setFormat('console');
  }

  protected function preprocessResult(Profile $profile, Target $target, Assessment $assessment)
  {

    $table_rows = [];
    $pass = [];
    foreach ($assessment->getResults() as $response) {
      $pass[] = $response->isSuccessful();

      $summary = [];
      foreach (explode(PHP_EOL, $response->getSummary()) as $line) {
        if (strlen($line) < 100) {
          $summary[] = $line;
          continue;
        }
        $words = preg_split('/ +/', $line);
        $phrase = [];
        while ($word = array_shift($words)) {
          $compound_line = $phrase;
          $compound_line[] = $word;
          if (strlen(implode(' ', $compound_line)) > 100) {
            $summary[] = implode(' ', $phrase);
            $phrase = ["    "];
          }
          $phrase[] = $word;
        }
        $summary[] = implode(' ', $phrase);
      }
      $summary = implode(PHP_EOL, $summary);

      $table_rows[] = [
        $this->getIcon($response),
        $response->getTitle(),
        $response->getSeverity(),
        $summary . (
          $response->isSuccessful() ? '' : PHP_EOL . PHP_EOL . $response->getRemediation()
        ),
      ];
      $table_rows[] = new TableSeparator();
    }

    $total_tests = count($assessment->getResults());
    $total_pass = count(array_filter($pass));
    $table_rows[] = ['', "$total_pass/$total_tests passed", ''];

    return [
      'title' => $profile->getTitle(),
      'table_rows' => $table_rows,
    ];
  }

  protected function renderResult(array $variables) {
    $io = new SymfonyStyle(new ArrayInput([]), $this->getOutput());
    $io->title($variables['title']);
    $io->table(['', 'Policy', 'Severity', 'Summary'], $variables['table_rows']);
  }

  protected function preprocessMultiResult(Profile $profile, Target $target, array $results)
  {

    // Set results by policy rather than by site.
    $resultsByPolicy = [];
    foreach ($results as $uri => $assessment) {
      foreach ($assessment->getResults() as $response) {
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
        '<options=bold>' . $policyInfo->getTitle() . '</>',
        $pass_rate . '% passed'
      ];
      $table_rows[] = [new TableCell($policyInfo->getDescription(), [
        'rowspan' => 2
        ])];

      foreach ($failed as $uri => $response) {
        $table_rows[] = ['- ' . $uri, ''];
      }

      $table_rows[] = new TableSeparator();
    }

    // Remove last table seperator
    array_pop($table_rows);

    return [
      'title' => $profile->getTitle(),
      'table_rows' => $table_rows,
    ];
  }

  protected function renderMultiResult(array $variables) {
    $io = new SymfonyStyle(new ArrayInput([]), $this->getOutput());
    $io->title($variables['title']);
    $io->table(['', 'Policy', 'Severity', 'Summary'], $variables['table_rows']);
  }

  protected function getIcon(AuditResponse $response)
  {
    if ($response->isNotice()) {
      return "ℹ️";
    }
    elseif ($response->hasWarning()) {
      return "⚠️";
    }
    else {
      return $response->isSuccessful() ? "✅" : "❌";
    }
  }
}

 ?>
