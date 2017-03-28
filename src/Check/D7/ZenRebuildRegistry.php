<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Zen rebuild registry",
 *  description = "The rebuild registry feature is enabled for your theme. This setting is only used during theme development, and can negatively impact site performance.",
 *  remediation = "To disable the rebuild theme registry feature, on your website, open the Themes page at <code>/admin/appearance/settings/[THEMENAME]</code>, and then deselect Rebuild theme registry for each enabled Zen-based theme. Also note that this setting is often hardcoded in theme info file.",
 *  success = "No themes with zen_rebuild_registry enabled.",
 *  failure = "There :prefix <code>:number_of_themes</code> theme:plural (:themes) with zen_rebuild_registry enabled.",
 *  exception = "Could not determine zen_rebuild_registry settings :exception.",
 * )
 */
class ZenRebuildRegistry extends Check {

  /**
   *
   */
  public function check() {

    $output = $this->context->drush->sqlQuery("SELECT * FROM {variable} WHERE name LIKE 'theme_%';");
    $themes_with_rebuild_enabled = [];
    foreach ($output as $row) {
      preg_match('/^theme_([a-zA-Z_]+)_settings/', $row, $matches);

      // 'theme_default' is also a variable we want to exclude.
      if (empty($matches)) {
        continue;
      }

      $theme_name = $matches[1];

      if (preg_match('/zen_rebuild_registry.;i:1/', $row)) {
        $themes_with_rebuild_enabled[] = $theme_name;
      }
    }

    if (count($themes_with_rebuild_enabled) > 0) {
      $this->setToken('number_of_themes', count($themes_with_rebuild_enabled));
      $this->setToken('themes', '<code>' . implode('</code>, <code>', $themes_with_rebuild_enabled) . '</code>');
      $this->setToken('plural', count($themes_with_rebuild_enabled) > 1 ? 's' : '');
      $this->setToken('prefix', count($themes_with_rebuild_enabled) > 1 ? 'are' : 'is');
      return FALSE;
    }

    return TRUE;
  }

}
