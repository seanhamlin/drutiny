<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;
use Drutiny\Annotation\Param;

/**
 * Generic module is disabled check.
 *
 * @Param(
 *  name = "module",
 *  description = "The module to check is enabled.",
 *  type = "string"
 * )
 */
class ModuleEnabled extends Audit implements RemediableInterface {

  /**
   *
   */
  public function audit(Sandbox $sandbox)
  {

    $module = $sandbox->getParameter('module');
    $info = $sandbox->drush(['format' => 'json'])->pmList();

    if (!isset($info[$module])) {
      return FALSE;
    }

    $status = strtolower($info[$module]['status']);

    return ($status == 'enabled');
  }

  public function remediate(Sandbox $sandbox)
  {
    $module = $sandbox->getParameter('module');
    $sandbox->drush()->en($module, '-y');
    return $this->check($sandbox);
  }

}
