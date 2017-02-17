<?php

use Drutiny\Check\D7\PreprocessCss;
use Drutiny\Base\DrushCaller;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Check\D7\PreprocessCss
 */
class PreprocessCSSTest extends TestCase
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
              ->will($this->onConsecutiveCalls(0, 1, '0', '1', TRUE, FALSE, NULL, 'banana', ''));

    // Create a new class, passing in the mock.
    $context = new Context();
    $context->set('drush', $drushStub)
            ->set('autoRemediate', FALSE);

    $check = new PreprocessCss($context);

    // Test it.
    $this->assertEquals(FALSE, $check->check()); // 0
    $this->assertEquals(TRUE, $check->check());  // 1
    $this->assertEquals(FALSE, $check->check()); // '0'
    $this->assertEquals(TRUE, $check->check());  // '1'
    $this->assertEquals(TRUE, $check->check());  // TRUE
    $this->assertEquals(FALSE, $check->check()); // FALSE
    $this->assertEquals(FALSE, $check->check()); // NULL
    $this->assertEquals(FALSE, $check->check()); // 'banana'
    $this->assertEquals(FALSE, $check->check()); // ''
  }
}
