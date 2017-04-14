<?php

namespace Drutiny\Driver;

/**
 *
 */
interface DrushInterface extends DriverInterface {

  /**
   *
   */
  public function __call($method, $args);

  /**
   *
   */
  public function runCommand($method, $args);

}
