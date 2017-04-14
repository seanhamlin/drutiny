<?php

namespace Drutiny\Driver;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
abstract class Driver implements DriverInterface {

  /**
   * @var Drutiny\Sandbox\Sandbox
   */
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
  protected function sandbox() {
    return $this->sandbox;
  }

  /**
   *
   */
  protected function log($input) {
    $this->sandbox()
      ->logger()
      ->info(get_class($this) . ': ' . $input);
  }

}
