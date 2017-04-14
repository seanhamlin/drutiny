<?php

namespace Drutiny\Driver;

/**
 *
 */
trait DrushTrait {

  protected $drushOptions = [];

  /**
   * Converts into method into a Drush command.
   */
  public function __call($method, $args) {
    // Convert method from camelCase to Drush hyphen based method naming.
    // E.g. PmInfo will become pm-info.
    preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches);
    $method = implode('-', array_map('strtolower', $matches[0]));
    $output = $this->runCommand($method, $args);

    if (in_array('--format=json', $this->drushOptions)) {
      if (!$output = json_decode($output, TRUE)) {
        throw new \Exception("Cannot parse json output from drush: $output");
      }
    }

    // Reset drush options.
    $this->drushOptions = [];

    return $output;
  }

  /**
   *
   */
  public function runCommand($method, $args) {
    return $this->sandbox()->exec('drush @options @method @args', [
      '@method' => $method,
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $this->drushOptions),
    ]);
  }

  /**
   *
   */
  public function setDrushOptions(array $options) {
    foreach ($options as $key => $value) {
      if (is_int($key)) {
        continue;
      }
      if (strlen($key) == 1) {
        $option = '-' . $key;
        if (!empty($value)) {
          $option .= ' ' . $value;
        }
      }
      else {
        $option = '--' . $key;
        if (!empty($value)) {
          $option .= '=' . $value;
        }
      }
      $this->drushOptions[] = $option;
    }
    return $this;
  }

}
