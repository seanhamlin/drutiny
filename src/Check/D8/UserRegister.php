<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "User registration",
 *  description = "Anonymous sites should have user registration set to off to prevent spam registrations.",
 *  remediation = "Set the configuration object <code>user.settings</code> key <code>register</code> to be <code>admin_only</code>.",
 *  success = "User registration is restricted to administrators only.",
 *  failure = "User registration is enabled for visitors.",
 *  exception = "Could not determine user registration settings.",
 * )
 */
class UserRegister extends Check {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $config = $sandbox->drush(['format' => 'json'])->configGet('user.settings', 'register');
    return $config['user.settings:register'] == 'admin_only';
  }

}
