<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Check\RemediableInterface;

/**
 * Generic module is disabled check.
 */
class ModuleDisabled extends Check implements RemediableInterface {

  /**
   *
   */
  public function check(Sandbox $sandbox)
  {

    $module = $sandbox->getParameter('module');
    $info = $sandbox->drush(['format' => 'json'])->pmInfo($module);
    $status = $info[$module]['status'];

    return ($status == 'not installed');
  }

  public function remediate(Sandbox $sandbox)
  {
    $module = $sandbox->getParameter('module');
    $sandbox->drush()->pmUninstall($module, '-y');
    return $this->check($sandbox);
  }

}
