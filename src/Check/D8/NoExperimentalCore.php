<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is disabled check.
 */
class NoExperimentalCore extends Check {

  /**
   *
   */
  public function check(Sandbox $sandbox)
  {

    $info = $sandbox->drush([
      'format' => 'json',
      'package' => 'Core (Experimental)',
      'status' => 'Enabled',
      'core',
    ])->pmList();

    if (empty($info)) {
      return TRUE;
    }

    $sandbox->setParameter('modules', array_values($info));
    return FALSE;
  }

}
