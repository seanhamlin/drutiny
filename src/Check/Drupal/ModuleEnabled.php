<?php

namespace Drutiny\Check\Drupal;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Check\RemediableInterface;

/**
 * Generic module is disabled check.
 */
class ModuleEnabled extends Check implements RemediableInterface {

  /**
   *
   */
  public function check(Sandbox $sandbox)
  {

    $module = $sandbox->getParameter('module');
    $info = $sandbox->drush(['format' => 'json'])->pmInfo($module);
    $status = $info[$module]['status'];

    return ($status == 'enabled');
  }

  public function remediate(Sandbox $sandbox)
  {
    $module = $sandbox->getParameter('module');
    $sandbox->drush()->en($module, '-y');
    return $this->check($sandbox);
  }

}
