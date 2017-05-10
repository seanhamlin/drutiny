<?php

namespace DrutinyTests\Check;

use DrutinyTests\Sandbox\SandboxStub;
use Drutiny\CheckInformation;
use Drutiny\Registry;

class SyslogEnabledTest extends CheckTestCase {

  public function testSyslogEnabled()
  {
    $info = $this->getCheckInfo('syslog.enabled');
    $sandbox = $this->createSandbox($info);
    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
  }

  public function testSyslogDisabled()
  {
    $info = $this->getCheckInfo('syslog.enabled');
    $sandbox = $this->createSandbox($info);
    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
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
