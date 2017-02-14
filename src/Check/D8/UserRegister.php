<?php

namespace SiteAudit\Check\D8;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "User registration",
 *  description = "Anonymous sites should have user registration set to off to prevent spam registrations.",
 *  remediation = "Set the configuration object <code>user.settings</code> key <code>register</code> to be <code>admin_only</code>.",
 *  success = "User registration is restricted to administrators only.",
 *  failure = "User registration is enabled for visitors.",
 *  exception = "Could not determine user registration settings.",
 * )
 */
class UserRegister extends Check {
  public function check()
  {
    $user_register = $this->context->drush->getConfig('user.settings', 'register', 'admin_only');
    return $user_register === 'admin_only';
  }
}
