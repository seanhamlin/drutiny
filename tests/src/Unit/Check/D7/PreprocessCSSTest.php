<?php

use Drutiny\Check\D7\PreprocessCSS;
use Drutiny\Base\DrushCaller;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Check\D7\PreprocessCSS
 */
class PreprocessCSSTest extends TestCase {

  /**
   * Check context.
   *
   * @var \Drutiny\Context
   */
  protected $context = NULL;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    // Create a stub for the DrushCaller class.
    $drushStub = $this->createMock(DrushCaller::class);
    $drushStub->method('getVariable')
      ->will($this->onConsecutiveCalls(0, 1, '0', '1', TRUE, FALSE, NULL, 'banana', ''));

    // Create a new class, passing in the mock.
    $this->context = new Context();
    $this->context->set('drush', $drushStub)
      ->set('autoRemediate', FALSE);
  }

  /**
   * @covers ::check
   * @group check
   */
  public function testCheck() {
    $check = new PreprocessCSS($this->context);

    // 0.
    $this->assertEquals(FALSE, $check->check());
    // 1.
    $this->assertEquals(TRUE, $check->check());
    // '0'.
    $this->assertEquals(FALSE, $check->check());
    // '1'.
    $this->assertEquals(TRUE, $check->check());
    // TRUE.
    $this->assertEquals(TRUE, $check->check());
    // FALSE.
    $this->assertEquals(FALSE, $check->check());
    // NULL.
    $this->assertEquals(FALSE, $check->check());
    // 'banana'.
    $this->assertEquals(FALSE, $check->check());
    // ''.
    $this->assertEquals(FALSE, $check->check());
  }

}
