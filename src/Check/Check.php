<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
abstract class Check implements CheckInterface {

  /**
   *
   */
  abstract public function check(Sandbox $sandbox);

  /**
   *
   */
  public function execute(Sandbox $sandbox)
  {
    $this->validate($sandbox);
    return $this->check($sandbox);
  }

  protected function validate(Sandbox $sandbox)
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
      throw new CheckValidationException("Check failed validation at " . $method->getDeclaringClass()->getFilename() . " [$method->name]: " . $e->getMessage());
    }
  }

}
