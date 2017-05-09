<?php

namespace Drutiny\Sandbox;

use Drutiny\Driver\Exec;
use Drutiny\Driver\ExecInterface;

/**
 *
 */
trait ExecTrait {

  /**
   *
   */
  public function exec() {
    $args = func_get_args();
    if ($this->target instanceof ExecInterface) {
      $driver = $this->target;
    }
    else {
      $driver = new Exec($this);
    }
    return call_user_func_array([$driver, 'exec'], $args);
  }

  /**
   *
   */
   public function localExec() {
     $args = func_get_args();
     $driver = new Exec($this);
     return call_user_func_array([$driver, 'exec'], $args);
   }

}
