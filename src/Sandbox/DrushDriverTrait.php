<?php

namespace Drutiny\Sandbox;

use Drutiny\Driver\Drush;
use Drutiny\Driver\DrushInterface;

/**
 *
 */
trait DrushDriverTrait {

  protected $drushOptions = [];

  /**
   *
   */
  public function drush($options = []) {
    $drush = ($this->target instanceof DrushInterface) ? $this->target : new Drush($this);
    $drush->setDrushOptions($options);
    return $drush;
  }

}
