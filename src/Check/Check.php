<?php

namespace Drutiny\Check;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * @deprecated
 *
 * Wrapper class to preferred class of Audit.
 */
abstract class Check extends Audit implements CheckInterface {

  /**
   * Backwards compatible method for audit().
   *
   * @param Sandbox $sandbox
   * @return
   */
  abstract public function check(Sandbox $sandbox);

  final public function audit(Sandbox $sandbox) {
    return $this->check($sandbox);
  }

}
