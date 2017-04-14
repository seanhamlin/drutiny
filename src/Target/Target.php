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
  public function parse() {
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

}
