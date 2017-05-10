<?php

namespace Drutiny\Sandbox;

use Drutiny\Target\Target;
use Drutiny\Check\CheckInterface;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\CheckInformation;
use Drutiny\Cache;

/**
 * Run check in an isolated environment.
 */
class Sandbox {
  use DrushDriverTrait;
  use ExecTrait;
  use ParameterTrait;
  use LoggerTrait;


  /**
   * @var \Drutiny\Target\Target
   */
  protected $target;

  /**
   * @var \Drutiny\Check\Check
   */
  protected $check;

  /**
   * @var \Drutiny\CheckInformation
   */
  protected $checkInfo;

  /**
   * Create a new Sandbox.
   *
   * @param string $target
   *   The class name of the target to create.
   *
   * @param Drutiny\CheckInformation $check
   *   The class name of the target to create.
   */
  public function __construct($target, CheckInformation $checkInfo) {
    $object = new $target($this);
    if (!$object instanceof Target) {
      throw new \InvalidArgumentException("$target is not a valid class for Target.");
    }
    $this->target = $object;

    $class = $checkInfo->get('class');
    $object = new $class($this);
    if (!$object instanceof CheckInterface) {
      throw new \InvalidArgumentException("Not a valid class for Check.");
    }
    $this->check = $object;
    $this->checkInfo = $checkInfo;
  }

  /**
   * Run the check and capture the outcomes.
   */
  public function run() {
    $response = new AuditResponse($this->checkInfo);

    try {
      $outcome = $this->getCheck()->execute($this);
      $response->set($outcome, $this->getParameterTokens());
    }
    catch (\Exception $e) {
      $this->setParameter('exception', $e->getMessage());
      $response->set(AuditResponse::ERROR, $this->getParameterTokens());
    }

    return $response;
  }

  /**
   * Remediate the check if available.
   */
  public function remediate() {
    $response = new AuditResponse($this->checkInfo);
    try {
      // Make sure remediation does report false positives due to caching.
      Cache::purge();
      $outcome = $this->getCheck()->remediate($this);
      $response->set($outcome, $this->getParameterTokens());
      if ($response->isSuccessful()) {
        $response->set(AuditResponse::REMEDIATED, $this->getParameterTokens());
      }
    }
    catch (\Exception $e) {
      $this->setParameter('exception', $e->getMessage());
      $response->set(AuditResponse::ERROR, $this->getParameterTokens());
    }

    return $response;
  }

  /**
   *
   */
  public function getCheck() {
    return $this->check;
  }

  /**
   *
   */
  public function getCheckInfo() {
    return $this->checkInfo;
  }

  /**
   *
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * A wrapper function for traits to use.
   */
  public function sandbox()
  {
    return $this;
  }
}
