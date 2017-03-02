<?php

/**
 * @file
 * Contains ${NAMESPACE}\TextFormatCheckTest
 */

namespace Drutiny\Tests\Security\D7;

use Drutiny\Check\Security\D7\TextFormat;
use Drutiny\Base\DrushCaller;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Context;
use PHPUnit\Framework\TestCase;

class TextFormatTest extends TestCase {

  /**
   * Create a mock to use for the abstract class.
   */
  public function setup() {

    $this->stub = $this->getMockBuilder(TextFormat::class)
      ->disableOriginalConstructor();

    $this->drush = $this->getMockBuilder(DrushCaller::class)
      ->methods(['sqlQuery',' invokeHook', 'getRolesForPermission'])
      ->getMock();

  }

  public function testGetFormats() {
    $query_results = [
      "raw\tRaw\t1\t0\t0",
      "rich_text\tRich Text\t1\0\0",
    ];

    $this->drush->expects($this->once())
      ->method('sqlQuery')
      ->will($this->returnValue($query_results));



  }

}
