<?php

namespace DrutinyTests\Audit;

use PHPUnit\Framework\TestCase;
use Drutiny\Sandbox\Sandbox;
use Psr\Log\NullLogger;

class PolicyTest extends TestCase {

  protected $registry;
  protected $targetClass;

  public function __construct()
  {
    $this->registry = new \Drutiny\Registry();
    $this->target = $this->registry->getTargetClass('none');
    parent::__construct();
  }

  public function testPass()
  {
    $policy = $this->registry->getPolicy('Test:Pass');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
  }

  public function testFail()
  {
    $policy = $this->registry->getPolicy('Test:Fail');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
  }

  public function testError()
  {
    $policy = $this->registry->getPolicy('Test:Error');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
    $this->assertTrue($response->hasError());
  }

  public function testWarning()
  {
    $policy = $this->registry->getPolicy('Test:Warning');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
    $this->assertTrue($response->hasWarning());
  }

  public function testNotApplicable()
  {
    $policy = $this->registry->getPolicy('Test:NA');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
    $this->assertTrue($response->isNotApplicable());
  }

  public function testNotice()
  {
    $policy = $this->registry->getPolicy('Test:Notice');
    $sandbox = new Sandbox($this->target, $policy);

    $sandbox->setLogger(new NullLogger());

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
    $this->assertTrue($response->isNotice());
  }

}
