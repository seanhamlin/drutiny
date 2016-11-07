<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Error level",
 *  description = "When PHP encounters an error, it can generate an error log and display a report on the screen. While these error messages can be helpful in debugging your site, they can be a security risk on a live site as they may reveal information about your server that can be used to compromise it.",
 *  remediation = "Set the variable <code>error_level</code> to be <code>0</code>.",
 *  success = "Errors are not shown on the screen.",
 *  failure = "Errors are shown on the screen, currently set to log <code>:error_level</code>.",
 *  exception = "Could not determine error level setting.",
 * )
 */
class ErrorLevel extends Check {
  public function check()
  {
    $error_level = (int) $this->context->drush->getVariable('error_level', 2);
    // @see https://github.com/drupal/drupal/blob/7.x/modules/system/system.admin.inc#L1671
    $human_names = [
      0 => 'none',
      1 => 'errors and warnings',
      2 => 'all messages',
    ];
    $this->setToken('error_level', $human_names[$error_level]);
    return $error_level === 0;
  }
}
