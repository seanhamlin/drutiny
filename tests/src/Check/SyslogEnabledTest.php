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

  public function stubPmList()
  {
    return [
      'syslog' => [
        'status' => 'enabled'
      ]
    ];
  }

}
