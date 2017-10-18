<?php

namespace Drutiny;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
interface AuditInterface {

  /**
   *
   */
  public function audit(Sandbox $sandbox);

  /**
   *
   */
  public function execute(Sandbox $sandbox);

}
