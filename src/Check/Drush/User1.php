<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 * title = "User #1",
 * description = "It is important to lock down user #1 in Drupal, this user is special an ignores access control.",
 * remediation = "Change the username to be random, set the email address to go nowhere, set the password to something secure.",
 * success = "User #1 is locked down.",
 * failure = "User #1 is not secure. :errors.",
 * exception = "Could not determine user #1 settings.",
 * )
 */
class User1 extends Check {
  public function check()
  {
    // Get the details for user #1.
    $user = (array) $this->context->drush->userInformation('--format=json', '1')->parseJson();
    $user = array_pop($user);

    $errors = [];

    // Username.
    $pattern = $this->getOption('name_blacklist', '(admin|root|drupal|god)');
    if (preg_match("#${pattern}#", $user->name)) {
      $errors[] = 'Username is too easy to guess - <code>' . $user->name . '</code>';
    }

    // Email address.
    $email = $this->getOption('mail', 'no_reply@example.com');
    if ($email !== $user->mail) {
      $errors[] = 'Email address is not set correctly - <code>' . $user->mail . '</code>';
    }

    // Status.
    $status = (bool) $this->getOption('status', 1);
    if ($status !== (bool) $user->status) {
      $errors[] = 'Status is not set correctly - <code>' . ($user->status ? 'active' : 'inactive') . '</code>';
    }

    // Remediation.
    //$new_username = $this->context->randomLib->generateRandomString();

    $this->setToken('errors', implode(', ', $errors));
    return empty($errors);
  }
}
