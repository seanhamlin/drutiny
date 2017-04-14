<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
abstract class Check implements CheckInterface {

  /**
   *
   */
  abstract public function check(Sandbox $sandbox);

  /**
   *
   */
  public function execute(Sandbox $sandbox) {
    $this->context = $sandbox;
    return $this->check($sandbox);
  }

}
