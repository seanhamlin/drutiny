<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
interface CheckInterface {

  /**
   *
   */
  public function check(Sandbox $sandbox);

  /**
   *
   */
  public function execute(Sandbox $sandbox);

}
