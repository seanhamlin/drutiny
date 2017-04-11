<?php
/**
 * @file
 * Contains Drutiny\Tests\Security\ViewsAccessBaseTest
 */

namespace Drutiny\Tests\Security;


use Drutiny\AuditResponse\AuditResponse;
use PHPUnit\Framework\TestCase;

/**
 * Class ViewsAccessD7Test
 *
 * @coversDefaultClass \Drutiny\Check\Security\D7\ViewsAccess
 */
class ViewsAccessD7Test extends TestCase  {

  protected $stub;

  /**
   * Create mocks and initialise the test.
   */
  public function setup() {
    $this->stub = $this->getMockBuilder('Drutiny\Check\Security\D7\ViewsAccess')
      ->disableOriginalConstructor()
      ->setMethods(['getViews', 'getOption'])
      ->getMock();
  }

  /**
   * Provide valid views data.
   *
   * @return array
   */
  public function provideViewsData() {
    $view = (object) [
      'name' => 'Test View',
      'display' => [
        'default' => (object) [
          'display_options' => [
            'access' => ['type' => 'perm', 'perm' => 'administrator'],
          ],
        ],
        'page1' => (object) [
          'display_options' => [
            'access' => ['type' => 'perm', 'perm' => 'administrator'],
          ],
        ],
      ]
    ];

    return [[[$view]]];
  }

  /**
   * Provide views with invalid permissions.
   *
   * @return array
   */
  public function provideInvalidViewsData() {
    $view = new \stdClass();

    $view = (object) [
      'name' => 'Test View',
      'display' => [
        'default' => (object) [
          'display_options' => [
            'access' => ['type' => NULL],
          ],
        ],
      ],
    ];

    return [[[$view]]];
  }

  /**
   * Provide views with invalid permission value.
   *
   * @return array
   */
  public function provideInvalidViewsPermissions() {
    $view = new \stdClass();

    $view = (object) [
      'name' => 'Test View',
      'display' => [
        'default' => (object) [
          'display_options' => [
            'access' => ['type' => 'perm', 'perm' => 'authenticated'],
          ],
        ],
      ],
    ];

    return [[[$view]]];
  }

  /**
   * Set the default return value.
   *
   * @param array $return
   *   The return value for the options.
   */
  public function setOptions($return = ['perm' => TRUE]) {
    $this->stub->expects($this->once())
      ->method('getOption')
      ->with('permissions', ['perm' => TRUE])
      ->willReturn($return);
  }

  /**
   * Ensure that views with valid permissions pass.
   *
   * @dataProvider provideViewsData
   */
  public function testValidPermissions($views = []) {
    $this->setOptions();

    $this->stub->expects($this->once())
      ->method('getViews')
      ->willReturn($views);

    $this->assertEquals(AuditResponse::AUDIT_SUCCESS, $this->stub->check());
  }

  /**
   * Ensure that class handles no views data correctly.
   */
  public function testNoViews() {
    $this->setOptions();

    $this->stub->expects($this->once())
      ->method('getViews')
      ->willReturn([]);

    $this->assertEquals(AuditResponse::AUDIT_NA, $this->stub->check());
  }

  /**
   * Ensure that views with invalid permissions are caught.
   *
   * @dataProvider provideInvalidViewsData
   */
  public function testCatchInvalidPermissions($views) {
    $this->setOptions();

    $this->stub->expects($this->once())
      ->method('getViews')
      ->willReturn($views);

    $this->assertEquals(AuditResponse::AUDIT_FAILURE, $this->stub->check());
  }

  /**
   * Ensure that if permissions are provided we test for those.
   *
   * @dataProvider provideViewsData
   */
  public function testExpectedPermissions($views) {
    $this->setOptions(['perm' => 'administrator']);

    $this->stub->expects($this->once())
      ->method('getViews')
      ->willReturn($views);

    $this->assertEquals(AuditResponse::AUDIT_SUCCESS, $this->stub->check());
  }

  /**
   * Ensure that fails are caught correctly.
   *
   * @dataProvider provideInvalidViewsPermissions
   */
  public function testExpectedPermissionFailure($views) {
    $this->setOptions(['perm' => 'administrator']);

    $this->stub->expects($this->once())
      ->method('getViews')
      ->willReturn($views);

    $this->assertEquals(AuditResponse::AUDIT_FAILURE, $this->stub->check());
  }

}
