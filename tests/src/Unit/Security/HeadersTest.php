<?php
/**
 * Created by PhpStorm.
 * User: steven.worley
 * Date: 7/03/2017
 * Time: 10:18 AM
 */

namespace Drutiny\Tests\Security;

use Drutiny\Check\Security\Headers;
use Drutiny\Base\DrushCaller;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Context;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class HeadersTest.
 *
 * @coversDefaultClass \Drutiny\Check\Security\Headers
 */
class HeadersTest extends TestCase {

  /**
   * Build a Guzzle mock object.
   */
  public function getClientMock($response = TRUE) {
    $client = $this->getMockBuilder('GuzzleHttp\Client')
      ->disableOriginalConstructor()
      ->setMethods(['request']);

    if ($response) {
      $response = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
        ->disableOriginalConstructor()
        ->setMethods(['getHeader'])
        ->getMock();

      $response->method('getHeader')
        ->will($this->onConsecutiveCalls(
          ['x-content-options' => TRUE],
          ['x-frame-options' => TRUE],
          ['x-xss-protection' => TRUE],
          ['content-security-policy' => TRUE]
        ));

      $client = $client->getMock();

      $client->method('request')->willReturn($response);
    }

    return $client;
  }

  /**
   * Build a phantom mock object.
   */
  public function getPhantomMock($auth = FALSE) {
    $phantom = $this->getMockBuilder('Drutiny\Base\PhantomasCaller')
      ->disableOriginalConstructor()
      ->setMethods(['getAuth', 'getDomain'])
      ->getMock();

    $phantom->method('getDomain')
      ->willReturn('https://test.com');

    if ($auth) {
      $phantom->expects($this->once())
        ->method('getAuth')
        ->willReturn(FALSE);
    }

    return $phantom;
  }

  /**
   * Create test mocks.
   */
  public function setup() {
    $this->stub = $this->getMockBuilder(Headers::class)
      ->disableOriginalConstructor()
      ->setMethods(['getOption', 'getPhantom', 'getClient'])
      ->getMock();
  }

  /**
   * Test a check will pass with minimal options.
   */
  public function testCheckPass() {
    $this->stub->expects($this->exactly(2))
      ->method('getOption')
      ->withConsecutive(
        ['expected_headers', []],
        ['defaults', TRUE]
      )
      ->will($this->onConsecutiveCalls([], TRUE));

    $this->stub->expects($this->once())->method('getClient')->will($this->returnValue($this->getClientMock(TRUE)));

    $this->stub->expects($this->any())
      ->method('getPhantom')
      ->willReturn($this->getPhantomMock(TRUE));

    $this->assertEquals(AuditResponse::AUDIT_SUCCESS, $this->stub->check());
  }

  /**
   * Test a check will fail with minimal options.
   */
  public function testUndefinedHeaderFails() {
    $this->stub->expects($this->exactly(2))
      ->method('getOption')
      ->withConsecutive(
        ['expected_headers', []],
        ['defaults', TRUE]
      )
      ->will($this->onConsecutiveCalls(['new-header-option' => 'Should be set'], TRUE));

    $this->stub->expects($this->once())->method('getClient')->will($this->returnValue($this->getClientMock(TRUE)));

    $this->stub->expects($this->any())
      ->method('getPhantom')
      ->willReturn($this->getPhantomMock(TRUE));

    $this->assertEquals(AuditResponse::AUDIT_FAILURE, $this->stub->check());
  }

  /**
   * Ensure that authentication details are added if required.
   */
  public function testAuthentication() {
    $expected_request_headers = [
      'auth' => ['username', 'password'],
    ];

    $this->stub->expects($this->exactly(2))
      ->method('getOption')
      ->withConsecutive(
        ['expected_headers', []],
        ['defaults', TRUE]
      )
      ->will($this->onConsecutiveCalls([], TRUE));

    $phantom = $this->getPhantomMock();

    $phantom->expects($this->once())
      ->method('getAuth')
      ->willReturn($expected_request_headers['auth']);

    $client = $this->getClientMock();

    $client->expects($this->once())
      ->method('request')
      ->with('GET', 'https://test.com', $expected_request_headers);

    $this->stub->expects($this->once())
      ->method('getClient')
      ->will($this->returnValue($client));


    $this->stub->expects($this->any())->method('getPhantom')->willReturn($phantom);

    // Execute the stub to ensure that the authentication is added correctly by using
    // expects- we can ensure that these methods will be called as expected.
    $this->stub->check();
  }

  /**
   * Ensure that empty headers are handled correctly.
   */
  public function testEmptyHeaders() {
    $this->stub->expects($this->exactly(2))
      ->method('getOption')
      ->withConsecutive(
        ['expected_headers', []],
        ['defaults', TRUE]
      )
      ->will($this->onConsecutiveCalls([], FALSE));

    $this->stub->expects($this->once())->method('getClient')->will($this->returnValue($this->getClientMock(TRUE)));

    $this->stub->expects($this->any())
      ->method('getPhantom')
      ->willReturn($this->getPhantomMock(TRUE));

    $this->assertEquals(AuditResponse::AUDIT_NA, $this->stub->check());
  }
}
