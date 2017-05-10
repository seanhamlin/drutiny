<?php

namespace Drutiny\Target;

/**
 * @Drutiny\Annotation\Target(
 *  name = "stub"
 * )
 */
class TargetStub extends Target {

  /**
   *
   */
  public function uri() {
    return 'http://default/';
  }
}
