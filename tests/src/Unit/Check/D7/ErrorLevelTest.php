<?php

use Drutiny\Check\D7\ErrorLevel;
use Drutiny\Base\DrushCaller;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Check\D7\ErrorLevel
 */
class ErrorLevelTest extends TestCase
{
  /**
   * @covers ::check
   * @group check
   */
  public function testCheck()
  {
    // Create a stub for the DrushCaller class.
    $drushStub = $this->createMock(DrushCaller::class);
    $drushStub->method('getVariable')
              ->will($this->onConsecutiveCalls(0, 1, 2, '0', '1', '2'));

    // Create a new class, passing in the mock.
    $context = new Context();
    $context->set('drush', $drushStub)
            ->set('autoRemediate', FALSE);

    $check = new ErrorLevel($context);

    $this->assertEquals(TRUE, $check->check());  // 0
    $this->assertEquals(FALSE, $check->check()); // 1
    $this->assertEquals(FALSE, $check->check()); // 2
    $this->assertEquals(TRUE, $check->check());  // '0'
    $this->assertEquals(FALSE, $check->check()); // '1'
    $this->assertEquals(FALSE, $check->check()); // '2'
  }
}
