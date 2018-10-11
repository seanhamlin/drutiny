<?php

namespace Drutiny;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\AuditResponse\NoAuditResponseFoundException;
use Drutiny\Target\TargetInterface;
use Drutiny\Sandbox\Sandbox;

class Assessment {
  /**
   * @var string URI
   */
  protected $uri;

  /**
   * @var array of AuditResponse objects
   */
  protected $results = [];

  public function __construct($uri = 'default')
  {
    $this->uri = $uri;
  }

  /**
   * Assess a Target.
   *
   * @param TargetInterface $target
   * @param array $policies each item should be a Drutiny\Policy object.
   * @param DateTime $start The start date of the reporting period. Defaults to -1 day.
   * @param DateTime $end The end date of the reporting period. Defaults to now.
   * @param bool $remediate If an Drutiny\Audit supports remediation and the policy failes, remediate the policy. Defaults to FALSE.
   */
  public function assessTarget(TargetInterface $target, array $policies, \DateTime $start = NULL, \DateTime $end = NULL, $remediate = FALSE)
  {
    $start = $start ?: new \DateTime('-1 day');
    $end   = $end ?: new \DateTime();

    $policies = array_filter($policies, function ($policy) {
      return $policy instanceof Policy;
    });

    $log = Container::getLogger();
    $is_progress_bar = $log instanceof ProgressBar;

    foreach ($policies as $policy) {
      if ($is_progress_bar) {
        $log->setTopic($this->uri . '][' . $policy->get('title'));
      }

      $log->info("Assessing policy...");

      // Setup the sandbox to run the assessment.
      $sandbox = new Sandbox($target, $policy, $this);
      $sandbox->setReportingPeriod($start, $end);

      $response = $sandbox->run();

      // Attempt remediation.
      if ($remediate && !$response->isSuccessful()) {
        $log->info("\xE2\x9A\xA0 Remediating " . $policy->get('title'));
        $response = $sandbox->remediate();
      }

      if ($is_progress_bar) {
        $log->advance();
      }
    }

    return $this;
  }

  /**
   * Set the result of a Policy.
   *
   * The result of a Policy is unique to an assessment result set.
   *
   * @param AuditResponse $response
   */
  public function setPolicyResult(AuditResponse $response)
  {
    $this->results[$response->getPolicy()->get('name')] = $response;
  }

  /**
   * Get an AuditResponse object by Policy name.
   *
   * @param string $name
   * @return AuditResponse
   */
  public function getPolicyResult($name)
  {
    if (!isset($this->results[$name])) {
      throw new NoAuditResponseFoundException($name, "Policy '$name' does not have an AuditResponse.");
    }
    return $this->results[$name];
  }

  /**
   * Get the results array of AuditResponse objects.
   *
   * @return array of AuditResponse objects.
   */
  public function getResults()
  {
    return $this->results;
  }

  /**
   * Get the uri of Assessment object.
   *
   * @return string uri.
   */
  public function uri()
  {
    return $this->uri;
  }
}

 ?>
