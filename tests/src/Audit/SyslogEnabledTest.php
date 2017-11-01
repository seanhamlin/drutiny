<?php

namespace DrutinyTests\Audit;

use DrutinyTests\Sandbox\SandboxStub;
use Drutiny\CheckInformation;
use Drutiny\Registry;

class SyslogEnabledTest extends AuditTestCase {

  public function testSyslogEnabled()
  {
    $this->assertPolicyPasses('Drupal:SyslogEnabled');
  }

  public function testSyslogDisabled()
  {
    $this->assertPolicyFails('Drupal:SyslogEnabled');
  }

  public function stubSyslogEnabledPmList()
  {
    return [
      'syslog' => [
        'status' => 'enabled'
      ]
    ];
  }

  public function stubSyslogDisabledPmList()
  {
    return [
      'syslog' => [
        'status' => 'not installed'
      ]
    ];
  }

}
