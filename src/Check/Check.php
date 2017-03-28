<?php

namespace Drutiny\Check;

use Drutiny\Context;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;
use Drutiny\Executor\ResultException;
use Doctrine\Common\Annotations\AnnotationReader;
use Drutiny\Annotation\CheckInfo;

/**
 * Base class for all checks.
 */
abstract class Check {

  protected $context;
  private $options;
  private $info;

  /**
   * Constructor.
   */
  public function __construct(Context $context, array $options = []) {
    $this->context = $context;
    $this->options = $options;
  }

  /**
   *
   */
  protected function getOption($name, $default = NULL) {
    return empty($this->options[$name]) ? $default : $this->options[$name];
  }

  /**
   *
   */
  protected function setToken($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

  /**
   *
   */
  protected function missingToken($name) {
    return !isset($this->options[$name]);
  }

  /**
   *
   */
  public function getTokens() {
    $tokens = [];

    // So we can support multidimensional arrays we convert the options array
    // to a recursive iterator- this will allow us to flatten arrays separated
    // with dot notation.
    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->options));

    foreach ($iterator as $leafValue) {
      $keys = array();
      foreach (range(0, $iterator->getDepth()) as $depth) {
        $keys[] = $iterator->getSubIterator($depth)->key();
      }
      $tokens[':' . join('.', $keys)] = $leafValue;
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
  protected function remediate() {
    return FALSE;
  }

  /**
   * Retrieve the CheckInfo object from the classes annotation.
   */
  final public function getInfo() {
    if (empty($this->info)) {
      $reflection = new \ReflectionClass($this);
      $reader = new AnnotationReader();
      $info = $reader->getClassAnnotation($reflection, 'Drutiny\Annotation\CheckInfo');
      $this->info = !empty($info) ? $info : new CheckInfo();
    }
    return $this->info;
  }

  /**
   * Execute the check in a sandbox.
   *
   * @return \Drutiny\AuditResponse\AuditResponse the outcome of the check.
   */
  public function execute() {
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

      // Set the fixups token if not already set to avoid it showing in any
      // messages.
      if ($this->missingToken('fixups')) {
        $this->setToken('fixups', '');
      }

      // Attempt to auto remediate the issue, but only if we have a failure, and
      // the user wants to remediate the issue.
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
