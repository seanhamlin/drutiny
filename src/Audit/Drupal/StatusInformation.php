<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Drush Status Information
 */
class StatusInformation extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();
    $sandbox->setParameter('status', $stat);

    return Audit::NOTICE;
  }

}
