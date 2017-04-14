<?php

namespace Drutiny\Driver;

/**
 *
 */
interface ExecInterface extends DriverInterface {

  /**
   *
   */
  public function exec($command, $args);

}
