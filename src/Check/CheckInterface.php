<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditInterface;

/**
 *
 */
interface CheckInterface extends AuditInterface {

  /**
   *
   */
  public function check(Sandbox $sandbox);

}
