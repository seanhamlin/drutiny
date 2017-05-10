<?php

namespace DrutinyTests\Check;

use PHPUnit\Framework\TestCase;
use DrutinyTests\Sandbox\SandboxStub;
use Drutiny\CheckInformation;
use Drutiny\Registry;
use Drutiny\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class CheckTestCase extends TestCase {

  protected function createSandbox(CheckInformation $info)
  {
    $sandbox = new SandboxStub('Drutiny\Target\TargetStub', $info);
    $sandbox->setLogger(new ConsoleLogger(new ConsoleOutput()));
    $sandbox->setTestCase($this);
    return $sandbox;
  }

  protected function getCheckInfo($checkname)
  {
    $checks = Registry::checks();
    $this->assertArrayHasKey($checkname, $checks);
    return $checks[$checkname];
  }

  /**
   * Asserts that a condition is true.
   *
   * @param  string $checkname
   * @param  array  $parameters
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertCheckPasses($checkname, $parameters = [])
  {
    $info = $this->getCheckInfo($checkname);
    $sandbox = $this->createSandbox($info);
    $sandbox->setParameters($parameters);
    $response = $sandbox->run();
    self::assertTrue($response->isSuccessful(), "$checkname passed");
  }

  /**
   * Asserts that a condition is true.
   *
   * @param  string $checkname
   * @param  array  $parameters
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertCheckFails($checkname, $parameters = [])
  {
    $info = $this->getCheckInfo($checkname);
    $sandbox = $this->createSandbox($info);
    $sandbox->setParameters($parameters);
    $response = $sandbox->run();
    self::assertFalse($response->isSuccessful(), "$checkname failed");
  }

}
