<?php

namespace Drutiny\Check\Drush;

use Drutiny\Base\RandomLib;
use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "User #1",
 *  description = "It is important to lock down user #1 in Drupal, this user is special an ignores access control.",
 *  remediation = "Change the username to be random, set the email address to go nowhere, set the password to something secure.",
 *  success = "User #1 is locked down.:fixups",
 *  failure = "User #1 is not secure.:errors",
 *  exception = "Could not determine user #1 settings.",
 *  supports_remediation = TRUE,
 * )
 */
class User1 extends Check {

  /**
   *
   */
  public function check() {
    // Get the details for user #1.
    $user = (array) $this->context->drush->userInformation('--format=json', '1')->parseJson();
    $user = array_pop($user);

    $errors = [];
    $fixups = [];

    // Username.
    $pattern = $this->getOption('name_blacklist', '(admin|root|drupal|god)');
    if (preg_match("#${pattern}#", $user->name)) {
      if ($this->context->autoRemediate) {
        $user->name = RandomLib::generateRandomString();
        $this->context->drush->sqlQuery("UPDATE {users} SET name = '$user->name' WHERE uid = 1;");
        $fixups[] = 'Username is now secure';
      }
      else {
        $errors[] = 'Username is too easy to guess - <code>' . $user->name . '</code>';
      }
    }

    // Email address.
    $email = $this->getOption('mail', 'no_reply@example.com');
    if ($email !== $user->mail) {
      if ($this->context->autoRemediate) {
        $this->context->drush->sqlQuery("UPDATE {users} SET mail = '$email' WHERE uid = 1;");
        $this->context->drush->sqlQuery("UPDATE {users} SET init = '$email' WHERE uid = 1;");
        $fixups[] = 'Email address is now secure';
      }
      else {
        $errors[] = 'Email address is not set correctly - <code>' . $user->mail . '</code>';
      }
    }

    // Status.
    $status = (bool) $this->getOption('status', 1);
    if ($status !== (bool) $user->status) {
      if ($this->context->autoRemediate) {
        $status_value = $status ? 1 : 0;
        $this->context->drush->sqlQuery("UPDATE {users} SET status = '$status_value' WHERE uid = 1;");
        $fixups[] = 'Status is now set to <code>' . ($status ? 'active' : 'inactive') . '</code>';
      }
      else {
        $errors[] = 'Status is not set correctly - <code>' . ($user->status ? 'active' : 'inactive') . '</code>';
      }
    }

    // Password gets updated if there are any fixups to do as a precaution.
    if ($this->context->autoRemediate) {
      if (!empty($fixups)) {
        $password = RandomLib::generateRandomString();
        $this->context->drush->userPassword("$user->name", "--password='$password'");
        $fixups[] = 'Password is now secure';
      }
    }

    $this->setToken('errors', ' ' . implode(', ', $errors));
    $this->setToken('fixups', ' ' . implode(', ', $fixups));
    return empty($errors);
  }

}
