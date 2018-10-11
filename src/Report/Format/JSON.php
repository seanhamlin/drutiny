<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\Target\Target;
use Drutiny\Assessment;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

class JSON extends Format {

  /**
   * The output location or method.
   *
   * @var string
   */
  protected $output;

  public function __construct($options) {
    $this->setFormat('json');
    if (isset($options['output'])) {
      $this->setOutput($options['output']);
    }
  }

  protected function preprocessResult(Profile $profile, Target $target, Assessment $assessment)
  {
    $schema = [
      'notices' => 0,
      'information' => 0,
      'warnings' => 0,
      'failures' => 0,
      'passes' => 0,
      'errors' => 0,
      'not_applicable' => 0,
      'remediated' => 0,
      'total' => 0,
    ];

    $schema['results'] = [];

    // Report Title.
    $schema['title'] = $profile->getTitle();
    $schema['profile'] = $profile->getName();
    $schema['domain'] = $profile->reportPerSite() === true ? $assessment->uri() : $target->uri();
    $schema['summary'] = $profile->reportPerSite() === true ? $assessment->uri() : $target->uri();
    $schema['description'] = $profile->getDescription();
    $schema['remediations'] = [];
    $schema['reporting_period_start'] = $profile->getReportingPeriodStart()->format('Y-m-d H:i:s e');
    $schema['reporting_period_end'] = $profile->getReportingPeriodEnd()->format('Y-m-d H:i:s e');
    $outcomes = [
      'success' => 0,
      'failure' => 0,
      'warning' => 0,
      'error' => 0,
      'not_applicable' => 0,
    ];
    $schema['stats'] = [
      'critical' => $outcomes,
      'high' => $outcomes,
      'medium' => $outcomes,
      'low' => $outcomes,
      'none' => $outcomes,
    ];

    foreach ($assessment->getResults() as $response) {
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
        'severity_code' => $response->getSeverityCode(),
        'exception' => $response->getExceptionMessage(),
        'name' => $response->getName(),
      ];

      $schema['total']++;

      switch ($response->getType()) {
        case 'data':
          $schema['information']++;
          $var['status_title'] = 'Information';
          break;

        case 'notice':
          $schema['notices']++;
          $var['status_title'] = 'Notice';
          break;

        case 'error':
          $schema['errors']++;
          $var['status_title'] = 'Error';
          $schema['stats'][$var['severity']]['error']++;
          break;

        case 'not-applicable':
          $schema['not_applicable']++;
          $var['status_title'] = 'Not Applicable';
          $schema['stats'][$var['severity']]['not_applicable']++;
          break;

        case 'warning':
          $schema['warnings']++;
          $var['status_title'] = 'Warning';
          $schema['stats'][$var['severity']]['warning']++;
          break;

        case 'success':
          $schema['passes']++;
          if ($response->isRemediated()) {
            $schema['remediated']++;
            $var['status_title'] = 'Remediated';
          }
          else {
            $var['status_title'] = 'Passed';
          }
          $schema['stats'][$var['severity']]['success']++;
          break;

        case 'failure':
          $schema['failures']++;
          $var['status_title'] = 'Failed';
          $schema['remediations'][] = $response->getRemediation();
          $schema['stats'][$var['severity']]['failure']++;
          break;
      }
      $schema['results'][] = $var;
      $schema['policy'][$var['name']] = $var;
    }

    $schema['stats'] = array_filter($schema['stats'], function ($a) {
      return count(array_filter($a));
    });

    foreach ($schema['stats'] as $severity => $results) {
      $schema['totals'][$severity] = array_sum($results);
    }

    return $schema;
  }

  protected function renderResult(array $variables)
  {
    return json_encode($variables);
  }

  protected function preprocessMultiResult(Profile $profile, Target $target, array $results)
  {
    $report = [
      'by_site' => [],
      'by_policy' => [],
      'sites' => []
    ];
    $resultsByPolicy = [];
    foreach ($results as $uri => $siteReport) {
      $report['sites'][] = $uri;
      foreach ($siteReport as $response) {
        $policy = [
          'isSuccessful' => $response->isSuccessful(),
          'hasWarning' => $response->hasWarning(),
          'hasError' => $response->hasError(),
          'isNotice' => $response->isNotice(),
          'isNotApplicable' => $response->isNotApplicable(),
          'message' => $response->getSummary(),
        ];
        if (!isset($report['by_policy'][$response->getName()])) {
          $report['by_policy'][$response->getName()] = [
            'sites' => [],
            'total' => 0,
            'success' => 0,
            'failure' => 0,
            'warning' => 0,
            'error' => 0,
            'not_applicable' => 0,
            'notice' => 0,
            'title' => $response->getTitle(),
            'description' => $response->getDescription(),
            'type' => $response->getType(),
            'name' => $response->getName(),
          ];
        }
        $report['by_policy'][$response->getName()]['sites'][$uri] = $policy;
        $report['by_policy'][$response->getName()]['total']++;
        $report['by_policy'][$response->getName()]['notice'] += $policy['isNotice'] ? 1 : 0;
        $report['by_policy'][$response->getName()]['success'] += (!$policy['isNotice'] && $policy['isSuccessful'] && !$policy['hasWarning'])   ? 1 : 0;
        $report['by_policy'][$response->getName()]['warning'] += $policy['hasWarning'] ? 1 : 0;
        $report['by_policy'][$response->getName()]['error'] += $policy['hasError'] ? 1 : 0;
        $report['by_policy'][$response->getName()]['not_applicable'] += $policy['isNotApplicable'] ? 1 : 0;
        $report['by_policy'][$response->getName()]['failure'] += (!$policy['isSuccessful'] && !$policy['hasError'] && !$policy['isNotApplicable']) ? 1 : 0;
        $report['by_site'][$uri][$response->getName()] = $policy['isSuccessful'];
      }
    }

    foreach ($report['by_policy'] as &$result) {
      $result['success_rate'] = round($result['success'] / $result['total'] * 100, 2);
      $result['failure_rate'] = round($result['failure'] / $result['total'] * 100, 2);
    }

    return $report;
  }

  protected function renderMultiResult(array $variables)
  {
    return $this->renderResult($variables);
  }
}

 ?>
