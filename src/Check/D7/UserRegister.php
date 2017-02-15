<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "User registration",
 *  description = "Anonymous sites should have user registration set to off to prevent spam registrations.",
 *  remediation = "Set the variable <code>user_register</code> to be <code>0</code>.",
 *  success = "User registration is restricted to administrators only.",
 *  failure = "User registration is enabled for visitors.",
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
