<?php

namespace DrutinyTests\Audit;

use PHPUnit\Framework\TestCase;
use DrutinyTests\Sandbox\SandboxStub;
use Drutiny\Policy;
use Drutiny\Registry;
use Drutiny\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AuditTestCase extends TestCase {

  protected function createSandbox(Policy $info)
  {
    $sandbox = new SandboxStub('Drutiny\Target\TargetStub', $info);
    $sandbox->setLogger(new ConsoleLogger(new ConsoleOutput()));
    $sandbox->setTestCase($this);
    return $sandbox;
  }

  protected function getPolicyInfo($checkname)
  {
    $checks = (new Registry())->policies();
    $this->assertArrayHasKey($checkname, $checks);
    return $checks[$checkname];
  }

  /**
   * Asserts that a condition is true.
   *
   * @param  string $policy
   * @param  array  $parameters
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertPolicyPasses($policy, $parameters = [])
  {
    $info = $this->getPolicyInfo($policy);
    $sandbox = $this->createSandbox($info);
    $sandbox->setParameters($parameters);
    $response = $sandbox->run();
    self::assertTrue($response->isSuccessful(), "$policy passed");
  }

  /**
   * Asserts that a condition is true.
   *
   * @param  string $policy
   * @param  array  $parameters
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertPolicyFails($policy, $parameters = [])
  {
    $info = $this->getPolicyInfo($policy);
    $sandbox = $this->createSandbox($info);
    $sandbox->setParameters($parameters);
    $response = $sandbox->run();
    self::assertFalse($response->isSuccessful(), "$policy failed");
  }

}
