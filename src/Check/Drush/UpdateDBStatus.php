<?php

namespace Drutiny\Check\Drush;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Database updates",
 *  description = "Updates to Drupal core or contrib modules sometimes include important database changes which should be applied after the code updates have been deployed.",
 *  remediation = "Required database updates should be applied by running <code>drush updatedb</code>.",
 *  success = "No database updates required.",
 *  failure = "There are pending updates to be run.",
 *  exception = "Could not determine status for the database for update.",
 * )
 */
class UpdateDBStatus extends Check {
  public function check()
  {
    $context = $this->context;
    $output = $context->drush->updatedbStatus()->getOutput();
    // Sometimes "No database updates required" is in Stderr, and thus is
    // empty.
    if (empty($output)) {
      return TRUE;
    }
    if (count($output) === 1) {
      $output = reset($output);
      if (strpos($output, 'No database updates required') === 0 || empty($output)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
