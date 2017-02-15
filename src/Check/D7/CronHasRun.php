<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Cron last run",
 *  description = "Cron should be run regularly to ensure that scheduled events are processed in a timely manner.",
 *  remediation = "Ensure a cron job has been configured for the site. If so, file a support ticket to investigate why cron has stopped working.",
 *  success = "Cron was last run on <code>:cron_last</code> (:date_default_timezone).",
 *  failure = "Cron was last run on <code>:cron_last</code> (:date_default_timezone).",
 *  exception = "Could not determine status of cron: :exception."
 * )
 */
class CronHasRun extends Check {
  public function check()
  {
    if (!$cron_last = $this->context->drush->getVariable('cron_last')) {
      throw new \Exception("Cron has not run on the site.");
    }

    // For accurate math, we need to set the timezone correctly.
    if (!$date_default_timezone = $this->context->drush->getVariable('date_default_timezone')) {
      $date_default_timezone = 'UTC';
    }
    date_default_timezone_set($date_default_timezone);

    // Check that cron was run in the last day.
    $since = time() - $cron_last;
    $this->setToken('cron_last', date('Y-m-d H:i:s', $cron_last));
    $this->setToken('date_default_timezone', $date_default_timezone);
    if ($since > (86400)) {
      return FALSE;
    }

    return TRUE;
  }
}
