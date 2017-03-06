<?php

namespace Drutiny\Check;

use Drutiny\Context;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;
use Drutiny\Executor\ResultException;
use Doctrine\Common\Annotations\AnnotationReader;
use Drutiny\Annotation\CheckInfo;

abstract class Check {

  protected $context;
  private $options;
  private $info;

  public function __construct(Context $context, Array $options = []) {
    $this->context = $context;
    $this->options = $options;
  }

  protected function getOption($name, $default = NULL) {
    return empty($this->options[$name]) ? $default : $this->options[$name];
  }

  protected function setToken($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

  public function getTokens() {
    $tokens = [];
    foreach ($this->options as $key => $value) {
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
      $tokens[':' . $key] = $value;
    }
    return $tokens;
  }

  /**
   * Run a sandboxed check.
   *
   * @return int AuditResponse::AUDIT_SUCCESS or similar.
   */
  abstract protected function check();

  /**
   * If the check has failed, then the user can opt to auto-remediate the issue.
   * Not all checks will implement this method.
   *
   * @return bool
   *   Whether or not the remediate method was run and was successful.
   */
  protected function remediate()
  {
    return FALSE;
  }

  /**
   * Retrieve CheckInfo object.
   */
  final public function getInfo()
  {
    if (empty($this->info)) {
      $reflection = new \ReflectionClass($this);
      $reader = new AnnotationReader();
      $info = $reader->getClassAnnotations($reflection);
      $this->info = !empty($info[0]) ? $info[0] : new CheckInfo();
    }
    return $this->info;
  }

  /**
   * Execute the check in a sandbox.
   *
   * @return AuditResponse the outcome of the check.
   */
  public function execute()
  {
    $response = new AuditResponse($this);

    try {
      $result = $this->check();

      // All constants are integers, check for them first.
      if (is_int($result)) {
        $response->setStatus($result);
      }
      // Booleans can also be used.
      else {
        switch ($result) {
          case TRUE:
            $response->setStatus(AuditResponse::AUDIT_SUCCESS);
            break;

          case FALSE:
            $response->setStatus(AuditResponse::AUDIT_FAILURE);
            break;
        }
      }

      // Attempt to auto remediate the issue, but only if we have a failure, and
      // the user wants to remediate the issue.
      $this->setToken('fixups', '');
      if ($response->getStatus() === AuditResponse::AUDIT_FAILURE && $this->context->autoRemediate) {
        if ($this->remediate()) {
          $response->setStatus(AuditResponse::AUDIT_SUCCESS);
          $this->setToken('fixups', ' This was auto remediated.');
        }
      }

    }
    catch (DoesNotApplyException $e) {
      $response->setStatus(AuditResponse::AUDIT_NA);
    }
    catch (ResultException $e) {
      $response->setStatus(AuditResponse::AUDIT_ERROR);
      $response->exception = $e;
    }
    catch (\Exception $e) {
      $response->setStatus(AuditResponse::AUDIT_ERROR);
      $response->exception = $e;
    }
    return $response;
  }
}
