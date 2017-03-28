<?php
/**
 * @file
 * Contains Drutiny\Tests\Security\TextFormatBaseCheck
 */

namespace Drutiny\Tests\Security;

use Drutiny\Check\Security\TextFormatBase;
use Drutiny\Base\DrushCaller;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

/**
 * Class TextFormatBaseTest.
 *
 * @coversDefaultClass \Drutiny\Check\Security\TextFormatBase
 */
class TextFormatBaseTest extends TestCase {

  /**
   * Create a mock to use for the abstract class.
   */
  public function setup() {
    $this->stub = $this->getMockBuilder('Drutiny\Check\Security\TextFormatBase')
      ->disableOriginalConstructor()
      ->setMethods(['getOption', 'getFormats'])
      ->getMock();

    $this->stub->expects($this->once())
      ->method('getOption')
      ->with('text_formats')
      ->will($this->returnValue(['filter_html' => ['unauthenticated']]));
  }

  /**
   * Provide a sample text format.
   *
   * @return array
   */
  public function provideFormats() {
    $formats = [
      'raw' => (object) [
        'format' => 'raw',
        'name' => 'Raw',
        'cache' => 1,
        'status' => 1,
        'weight' => 0,
        'roles' => ['administrator' => [], 'authenticated' => [], 'unauthenticated' => []],
        'filters' => (object) [
          'filter_html' => (object) ['filter' => 'filter_html'],
        ],
      ],
    ];

    return [[$formats]];
  }

  /**
   * Ensure that a check will return errors.
   *
   * If the text format option contains a role that exists within the configured
   * text formats this is considered an error and we should catch this text
   * format.
   *
   * @dataProvider provideFormats
   */
  public function testCheckCatchError($formats) {
    $this->stub->expects($this->once())
      ->method('getFormats')
      ->will($this->returnValue($formats));

    $this->assertEquals(AuditResponse::AUDIT_WARNING, $this->stub->check());
  }

  /**
   * Ensure that a check will return successful.
   *
   * If the configured text formats do not contain the listed roles then this
   * check is considered a pass.
   *
   * @dataProvider provideFormats
   */
  public function testCheckPass($formats) {
    $formats['raw']->roles = ['administrator'];

    $this->stub->expects($this->once())
      ->method('getFormats')
      ->will($this->returnValue($formats));

    $this->assertEquals(AuditResponse::AUDIT_SUCCESS, $this->stub->check());
  }

  /**
   * Ensure the check will not cause errors if filters are not defined.
   *
   * @dataProvider provideFormats
   */
  public function testFilterNotAvailable($formats) {
    unset($formats['raw']->filters->filter_html);

    $this->stub->expects($this->once())
      ->method('getFormats')
      ->will($this->returnValue($formats));

    $this->assertEquals(AuditResponse::AUDIT_SUCCESS, $this->stub->check());
  }

}
