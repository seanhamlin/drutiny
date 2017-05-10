<?php

namespace DrutinyTests\Check;

use DrutinyTests\Sandbox\SandboxStub;
use Drutiny\CheckInformation;
use Drutiny\Registry;

class SyslogEnabledTest extends CheckTestCase {

  public function testSyslogEnabled()
  {
    $this->assertCheckPasses('syslog.enabled');
  }

  public function testSyslogDisabled()
  {
    $this->assertCheckFails('syslog.enabled');
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
