<?php

namespace Drutiny;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Check\AuditValidationException;

/**
 *
 */
abstract class Audit implements AuditInterface {

  /**
   * Return a successful audit outcome.
   */
  const SUCCESS = TRUE;

  /**
   * Return a successful audit outcome.
   *
   * @Synonum for Audit::SUCCESS.
   */
  const PASS = 1;

  /**
   * Return a failed audit outcome.
   */
  const FAIL = FALSE;

  /**
   * Return a failed audit outcome.
   *
   * @Synonum for Audit::FAIL.
   */
  const FAILURE = 0;

  /**
   * An audit returned non-assertive information.
   */
  const NOTICE = 2;

  /**
   * An audit returned success with a warning.
   */
  const WARNING = 4;

  /**
   * An audit returned failure with a warning.
   */
  const WARNING_FAIL = 8;

  /**
   * An audit did not complete and returned an error.
   */
  const ERROR = 16;

  /**
   * An audit was not applicable to the target.
   */
  const NOT_APPLICABLE = -1;

  /**
   *
   */
  abstract public function audit(Sandbox $sandbox);

  /**
   *
   */
  final public function execute(Sandbox $sandbox)
  {
    $this->validate($sandbox);
    return $this->audit($sandbox);
  }

  final protected function validate(Sandbox $sandbox)
  {
    $reflection = new \ReflectionClass($this);

    // Call any functions that begin with "require" considered
    // prerequisite classes.
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
    $validators = array_filter($methods, function ($method) {
      return strpos($method->name, 'require') === 0;
    });

    try {
      foreach ($validators as $method) {
        if (call_user_func([$this, $method->name], $sandbox) === FALSE) {
          throw new \Exception("Validation failed.");
        }
      }
    }
    catch (\Exception $e) {
      throw new AuditValidationException("Audit failed validation at " . $method->getDeclaringClass()->getFilename() . " [$method->name]: " . $e->getMessage());
    }
  }

}
