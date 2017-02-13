<?php

namespace SiteAudit\Check\D7;

use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Date timezone",
 *  description = "Tests to ensure the site has a timezone that matches what is expected. If the timezone is set incorrectly it can result in dates that make no sense to your end users.",
 *  remediation = "Set the timezone correctly.",
 *  success = "Date timezone is configured correctly, currently set to <code>:date_default_timezone</code>.",
 *  failure = "Date timezone is not configured correctly. :errors",
 *  exception = "Could not determine date timezone settings.",
 * )
 */
class DateDefaultTimezone extends Check {
  public function check() {
    // This defaults to "date_default_timezone_get()" in Drupal.
    $date_default_timezone = $this->context->drush->getVariable('date_default_timezone', '');
    $this->setToken('date_default_timezone', $date_default_timezone);

    $errors = [];
    $matches = FALSE;
    $timezone_whitelist = $this->getOption('timezone_whitelist', []);
    foreach ($timezone_whitelist as $timezone) {
      if ($timezone === $date_default_timezone) {
        $matches = TRUE;
        break;
      }
    }
    if (!$matches) {
      $errors[] = 'Date timezone does not match allowed timezones - <code>date_default_timezone</code> is set to <code>' . $date_default_timezone . '</code>. Allowed values are: <code>' . implode('</code>, <code>', $timezone_whitelist) . '</code>.';
    }

    $this->setToken('errors', implode(', ', $errors));
    return empty($errors);
  }
}
