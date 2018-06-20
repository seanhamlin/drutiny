<?php

namespace Drutiny\Target;

interface DrushTargetInterface extends TargetInterface {
  /**
   * Return an array of Drush options from the Target site-alias.
   */
  public function getOptions();

  /**
   * Return the Drush site-alias.
   */
  public function getAlias();
}
