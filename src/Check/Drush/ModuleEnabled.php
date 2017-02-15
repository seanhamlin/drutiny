<?php

namespace Drutiny\Check\Drush;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Module enabled",
 *  description = "Check that a set of modules are enabled.",
 *  remediation = "Enable the modules through the Drupal admin UI or drush en command.",
 *  success = "All modules (:modules) are enabled.",
 *  failure = "The following modules are not enabled: :not_enabled.",
 *  exception = "Could not successfully conduct check for enabled modules.",
 * )
 */
class ModuleEnabled extends Check {
  public function check()
  {
    $modules = $this->getOption('modules');
    if (empty($modules)) {
      return TRUE;
    }
    $this->setToken('modules', '<code>' . implode('</code>, <code>', $modules) . '</code>');

    $not_enabled = [];
    foreach ($modules as $module_name) {
      try {
        if (!$this->context->drush->moduleEnabled($module_name)) {
          throw new \Exception($module_name);
        }
      }
      catch (\Exception $e) {
        $not_enabled[] = $module_name;
      }
    }

    if (!empty($not_enabled)) {
      $this->setToken('not_enabled', '<code>' . implode('</code>, <code>', $not_enabled) . '</code>');
      return FALSE;
    }

    return TRUE;
  }
}
