<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface as CoreRemediableInterface;

/**
 *
 */
interface RemediableInterface extends CoreRemediableInterface {

  /**
   *
   */
  public function remediate(Sandbox $sandbox);

}
