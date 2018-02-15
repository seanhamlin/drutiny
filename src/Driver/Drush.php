<?php

namespace Drutiny\Driver;

/**
 *
 */
class Drush extends Driver {
  use DrushTrait;

  public function helper()
  {
    return new DrushHelper($this);
  }
}
