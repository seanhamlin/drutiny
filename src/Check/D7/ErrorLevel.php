<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Error level",
 *  description = "When PHP encounters an error, it can generate an error log and display a report on the screen. While these error messages can be helpful in debugging your site, they can be a security risk on a live site as they may reveal information about your server that can be used to compromise it.",
 *  remediation = "Set the variable <code>error_level</code> to be <code>0</code>.",
 *  success = "Errors are not shown on the screen.:fixups",
 *  failure = "Errors are shown on the screen, currently set to log <code>:error_level</code>.",
 *  exception = "Could not determine error level setting.",
 *  supports_remediation = TRUE,
 * )
 */
class ErrorLevel extends Check {

  /**
   * @inheritDoc
   */
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

  /**
   * @inheritDoc
   */
  public function remediate()
  {
    $res = $this->context->drush->setVariable('error_level', 0);
    return $res->isSuccessful();
  }
}
