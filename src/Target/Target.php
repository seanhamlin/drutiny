<?php

namespace Drutiny\Target;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
abstract class Target {
  private $sandbox;

  /**
   *
   */
  public function __construct(Sandbox $sandbox) {
    $this->sandbox = $sandbox;
  }

  /**
   *
   */
  public function parse($target_data) {
    return $this;
  }

  /**
   *
   */
  protected function sandbox() {
    return $this->sandbox;
  }

  /**
   *
   */
  abstract public function uri();

  /**
   * Parse a target argument into the target driver and data.
   */
  static public function parseTarget($target)
  {
    $target_name = 'drush';
    $target_data = $target;
    if (strpos($target, ':') !== FALSE) {
      list($target_name, $target_data) = explode(':', $target, 2);
    }
    return [$target_name, $target_data];
  }

}
