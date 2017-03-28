<?php

namespace Drutiny\Check\Drush;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Module disabled",
 *  description = "Check that a set of modules are disabled.",
 *  remediation = "Disable the modules through the Drupal admin UI or drush dis command.",
 *  success = "All modules (:modules) are disabled.",
 *  failure = "The following modules are not disabled: :enabled.",
 *  exception = "Could not successfully conduct check for disabled modules."
 * )
 */
class ModuleDisabled extends Check {

  /**
   *
   */
  public function check() {
    $modules = $this->getOption('modules');
    if (empty($modules)) {
      return TRUE;
    }
    $this->setToken('modules', '<code>' . implode('</code>, <code>', $modules) . '</code>');

    $enabled = [];
    foreach ($modules as $module_name) {
      try {
        if ($this->context->drush->moduleEnabled($module_name)) {
          throw new \Exception($module_name);
        }
      }
      catch (\Exception $e) {
        $enabled[] = $module_name;
      }
    }

    if (!empty($enabled)) {
      $this->setToken('enabled', '<code>' . implode('</code>, <code>', $enabled) . '</code>');
      return FALSE;
    }

    return TRUE;
  }

}
