<?php

namespace DrutinyTests\Sandbox;

use Drutiny\Sandbox\Sandbox;
use DrutinyTests\Check\CheckTestCase;
use PHPUnit\Framework\TestCase;
use Drutiny\AuditResponse\AuditResponse;

class SandboxStub extends Sandbox {

  protected $test;

  public function setTestCase(CheckTestCase $test)
  {
    $this->test = $test;
    return $this;
  }

  /**
   * Implements ExecTrait::exec().
   */
  public function exec() {
    $args = func_get_args();
    TestCase::assertTrue(method_exists($this->test, 'stubExec'));
    return call_user_func_array([$this->test, 'stubExec'], $args);
  }

  /**
   * Implements ExecTrait::localExec().
   */
   public function localExec() {
     $args = func_get_args();
     TestCase::assertTrue(method_exists($this->test, 'stubLocalExec'));
     return call_user_func_array([$this->test, 'stubLocalExec'], $args);
   }

   public function drush($options = []) {
     return $this;
   }

   public function __call($method, $args) {

     $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

     while ($stack = array_shift($stacktrace)) {
       if (isset($stack['class']) && (get_class($this->test) == $stack['class']) && strpos($stack['function'], 'test') === 0) {
         $caller = $stack['function'];
         break;
       }
     }

     if (isset($caller)) {
       $method = 'stub' . substr($caller, 4) . ucwords($method);
     }
     else {
       $method = 'stub' . ucwords($method);
     }

     if (!method_exists($this->test, $method)) {
       throw new \Exception(get_class($this->test) . ' is missing stub method: ' . $method);
     }
     return call_user_func_array([$this->test, $method], $args);
   }

   /**
    * Run the check and capture the outcomes.
    */
   public function run() {
     $response = new AuditResponse($this->checkInfo);

     $outcome = $this->getCheck()->execute($this);
     $response->set($outcome, $this->getParameterTokens());
     return $response;
   }
}
 ?>
