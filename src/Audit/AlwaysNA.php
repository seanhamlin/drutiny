<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * An audit that always not applicable.
 */
class AlwaysNA extends Audit {

  protected function requireIneligibility(Sandbox $sandbox)
  {
    throw new \Exception("This target is not applicable to " . __CLASS__);
  }

  public function audit(Sandbox $sandbox)
  {
    // This should never trigger.
    return Audit::FAIL;
  }
}

 ?>
