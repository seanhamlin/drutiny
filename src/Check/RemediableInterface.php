<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
interface RemediableInterface extends CheckInterface {

  /**
   *
   */
  public function remediate(Sandbox $sandbox);

}
