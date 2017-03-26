<?php

use Drutiny\Check\Sample\SamplePass;
use Drutiny\Check\Sample\SampleWarning;
use Drutiny\Check\Sample\SampleFailure;
use Drutiny\Check\Sample\SampleException;
use Drutiny\Base\DrushCaller;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\AuditResponse\AuditResponse
 */
class JsonReportTest extends TestCase {

  protected $context = NULL;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    $drushStub = $this->createMock(DrushCaller::class);
    $this->context = new Context();
    $this->context->set('drush', $drushStub)
      ->set('autoRemediate', FALSE);
  }

  /**
   * @covers ::jsonSerialize
   * @group check
   */
  public function testSuccess() {
    $check = new SamplePass($this->context);
    $response = $check->execute();
    $this->assertEquals(json_encode($response), '{"check":{"title":"Sample pass","description":"Sample pass descripion."},"status":"success","message":"Sample pass success."}');
  }

  /**
   * @covers ::jsonSerialize
   * @group check
   */
  public function testWarning() {
    $check = new SampleWarning($this->context);
    $response = $check->execute();
    $this->assertEquals(json_encode($response), '{"check":{"title":"Sample warning","description":"Sample warning descripion."},"status":"warning","message":"Sample warning warning."}');
  }

  /**
   * @covers ::jsonSerialize
   * @group check
   */
  public function testFailure() {
    $check = new SampleFailure($this->context);
    $response = $check->execute();
    $this->assertEquals(json_encode($response), '{"check":{"title":"Sample failure","description":"Sample failure descripion."},"status":"failure","message":"Sample failure failure."}');
  }

  /**
   * @covers ::jsonSerialize
   * @group check
   */
  public function testException() {
    $check = new SampleException($this->context);
    $response = $check->execute();
    $this->assertEquals(json_encode($response), '{"check":{"title":"Sample exception","description":"Sample exception descripion."},"status":"exception","message":"Sample exception exception."}');
  }

}
