<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Shield disabled",
 *  description = "The shield module protects Drupal sites from prying eyes, often it is used to protect sites that are not yet live, but should never be enabled for live sites.",
 *  remediation = "Disable shield through the shield user interface, set the config <code>shield.settings:user</code> to '' (blank), or uninstall the shield module.",
 *  success = "Shield is disabled.",
 *  failure = "Shield is enabled.",
 *  exception = "Could not determine shield setting.",
 * )
 */
class ShieldDisabled extends Check {

  /**
   *
   */
  public function check() {
    return !$this->context->drush->getConfig('shield.settings', 'user', FALSE);
  }

}
