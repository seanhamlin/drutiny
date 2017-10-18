<?php

namespace Drutiny;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
interface RemediableInterface extends AuditInterface {

  /**
   *
   */
  public function remediate(Sandbox $sandbox);

}
