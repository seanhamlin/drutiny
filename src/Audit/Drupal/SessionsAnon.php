<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Anonymous sessions
 */
class SessionsAnon extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    // Exclude openid sessions, as these are for people that are trying to login
    // but perhaps have not made it the whole way yet.
    $count = (int) $sandbox->drush()->sqlq("SELECT COUNT(*) FROM sessions WHERE uid = 0 AND session NOT LIKE 'openid%' AND session NOT LIKE '%Access denied%';");

    $sandbox->setParameter('sessions', $count);
    $sandbox->setParameter('plural', $count > 1 ? 's' : '');
    $sandbox->setParameter('prefix', $count > 1 ? 'are' : 'is');

    return $count === 0;
  }

}
