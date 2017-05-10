<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\D8\ModuleDisabled as D8ModuleDisabled;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is disabled check.
 */
class ModuleDisabled extends D8ModuleDisabled {

  public function remediate(Sandbox $sandbox)
  {
    $module = $sandbox->getParameter('module');
    $sandbox->drush()->dis($module, '-y');
    return $this->check($sandbox);
  }

}
