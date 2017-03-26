<?php

use Drutiny\Check\D7\PreprocessCSS;
use Drutiny\Base\DrushCaller;
use Drutiny\Executor\Result;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Check\Check
 */
class RemediationTest extends TestCase {

  /**
   * @covers ::execute
   * @group check
   */
  public function testRemediation() {
    // Create a stub for the DrushCaller class.
    $drushStub = $this->createMock(DrushCaller::class);
    $drushStub->method('getVariable')
      ->will($this->onConsecutiveCalls(0, 1));

    $resultStub = $this->createMock(Result::class);
    $resultStub->method('isSuccessful')
      ->willReturn(TRUE);

    $drushStub->expects($this->once())
      ->method('setVariable')
      ->will($this->returnValue($resultStub));

    // Create a new class, passing in the mock.
    $context = new Context();
    $context->set('drush', $drushStub)
      ->set('autoRemediate', TRUE);

    // First time around, the check will fail as getVariable() will return 0.
    // We expect auto remediation to occur.
    $check = new PreprocessCSS($context);
    $response = $check->execute();

    $this->assertEquals($response->getStatus(), AuditResponse::AUDIT_SUCCESS);
    $this->assertEquals((string) $response, '<info>CSS aggregation is enabled. This was auto remediated.</info>');

    // Second time around, but this time getVariable() will return 1, so no auto
    // remediation should occur.
    $check = new PreprocessCSS($context);
    $response = $check->execute();

    $this->assertEquals($response->getStatus(), AuditResponse::AUDIT_SUCCESS);
    $this->assertEquals((string) $response, '<info>CSS aggregation is enabled.</info>');
  }

}
