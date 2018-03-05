<?php

namespace Drutiny\Driver;

use Drutiny\Sandbox\Sandbox;

/**
 *
 */
abstract class Driver implements DriverInterface {

  /**
   * @var Sandbox
   */
  private $sandbox;

  /**
   * @param Sandbox $sandbox
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
   * Interesting events.
   */
  protected function logInfo($input) {
    $this->sandbox()
      ->logger()
      ->info(get_class($this) . ': ' . $input);
  }

  /**
   * Detailed debug information.
   */
  protected function logDebug($input) {
    $this->sandbox()
      ->logger()
      ->debug(get_class($this) . ': ' . $input);
  }

}
