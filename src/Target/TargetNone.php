<?php

namespace Drutiny\Target;

/**
 * @Drutiny\Annotation\Target(
 *  name = "none"
 * )
 */
class TargetNone extends Target {

  /**
   *
   */
  public function uri() {
    return 'http://default/';
  }
}
