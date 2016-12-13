<?php

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Shield disabled",
 *  description = "The shield module protects Drupal sites from prying eyes, often it is used to protect sites that are not yet live, but should never be enabled for live sites.",
 *  remediation = "Disable shield through the shield user interface, set the variable <code>shield_enabled</code> to 0.",
 *  success = "Shield is disabled.",
 *  failure = "Shield is enabled.",
 *  exception = "Could not determine shield setting.",
 * )
 */
class ShieldDisabled extends Check {
  public function check() {
    return ! $this->context->drush->isShieldEnabled();
  }
}
