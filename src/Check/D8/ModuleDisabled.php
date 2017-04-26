<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Check\RemediableInterface;
use Drutiny\Driver\DrushFormatException;

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

    try {
      // Drush pm-info doesn't always return json for this function.
      // For example if the module is not present.
      $info = $sandbox->drush(['format' => 'json'])->pmInfo($module);
    }
    catch (DrushFormatException $e) {
      return strpos($e->getOutput(), $module . ' was not found.') !== FALSE;
    }
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
