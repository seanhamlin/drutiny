<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Database updates
 */
class ModuleUpdateStatus extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $output = $sandbox->drush()->pmUpdatestatus('--format=json');
    $lines = explode(PHP_EOL, $output);
    $lines = array_map('trim', $lines);

    // Output can often contain non-json output which needs to be filtered out.
    while ($line = array_shift($lines)) {
      if ($line == "{") {
        array_unshift($lines, $line);
        break;
      }
    }
    $json = implode(PHP_EOL, $lines);
    $modules = json_decode($json, TRUE);

    $issues = [];

    foreach ($modules as $name => $info) {

      if (isset($info['recommended_major']) && $info['existing_major'] != $info['recommended_major']) {
        $issues[] = $info;
      }
      elseif ($info['existing_version'] != $info['recommended']) {
        $issues[] = $info;
      }
    }

    $sandbox->setParameter('updates', $issues);

    if (!count($issues)) {
      return TRUE;
    }

    $sec_updates = array_filter($issues, function ($info) {
      return strpos($info['status_msg'], 'SECURITY') !== FALSE;
    });

    // Pure failure if all issues are security ones.
    if (count($sec_updates) === count($issues)) {
      return FALSE;
    }
    // Security updates and normal updates available.
    elseif (count($sec_updates)) {
      return Audit::WARNING_FAIL;
    }

    // Just normal updates available.
    return Audit::WARNING;
  }

}
