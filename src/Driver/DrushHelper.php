<?php

namespace Drutiny\Driver;

/**
 *
 */
class DrushHelper {

  protected $drush;

  public function __construct(Drush $drush)
  {
    $this->drush = $drush;
  }

  /**
   * Function like the Drupal 7 variable_get function.
   *
   * @param $name
   *   The name of the variable (exact).
   * @param mixed $default
   *   The value to return if the variable is not set.
   */
  public function variable_get($name, $default = NULL)
  {
    $vars = $this->drush->setDrushOptions(['format' => 'json'])->variableGet();
    if (!isset($vars[$name])) {
      return $default;
    }
    return $vars[$name];
  }

}
