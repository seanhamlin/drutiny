<?php

namespace Drutiny;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
interface RemediableInterface extends AuditInterface {

  /**
   * Attempt to remediate the check after it has failed.
   *
   * @param Sandbox $sandbox
   * @return bool
   */
  public function remediate(Sandbox $sandbox);

}
