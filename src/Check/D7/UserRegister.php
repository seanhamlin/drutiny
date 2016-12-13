<?php

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "User registration",
 *  description = "Anonymous sites should have user registration set to off to prevent spam registrations.",
 *  remediation = "Set the variable <code>user_register</code> to be <code>0</code>.",
 *  success = "User registration is disabled.",
 *  failure = "User registration is enabled.",
 *  exception = "Could not determine user registration settings.",
 * )
 */
class UserRegister extends Check {
  public function check()
  {
    // @see USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL.
    $user_register = (int) $this->context->drush->getVariable('user_register', 2);
    return $user_register === 0;
  }
}
