<?php

namespace Drutiny\Check\Drush;

use Drutiny\Base\RandomLib;
use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Mass password reset",
 *  description = "Forces all user accounts to have a new password applied to them. All sessions are also destroyed.",
 *  remediation = "Run this check with auto-remediation enabled. This check will never pass on it's own.",
 *  success = "All passwords have been changed, and all users have been logged out.:fixups",
 *  failure = "No passwords reset.",
 *  exception = "Could not reset user passwords.",
 *  supports_remediation = TRUE,
 * )
 */
class MassPasswordReset extends Check {

  /**
   * @inheritdoc
   */
  public function check() {
    return AuditResponse::AUDIT_FAILURE;
  }

  /**
   * @inheritdoc
   */
  public function remediate() {
    // Get all user names for users.
    $users = $this->context->drush->sqlQuery("SELECT name FROM {users} WHERE uid > 0;");

    // Reset the passwords.
    foreach ($users as $user) {
      $password = RandomLib::generateRandomString();
      $this->context->drush->userPassword("'$user'", "--password='$password'");
    }

    // Truncate sessions.
    $this->context->drush->sqlQuery("TRUNCATE TABLE {sessions};");
    return TRUE;
  }

}
