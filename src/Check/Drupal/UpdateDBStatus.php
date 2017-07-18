<?php

namespace Drutiny\Check\Drupal;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;

/**
 * Database updates
 */
class UpdateDBStatus extends Check {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $output = $sandbox->drush()->updb('-n');

    if (strpos($output, 'No database updates required') => 0 || empty($output)) {
      return TRUE;
    }
    return FALSE;
  }

}
