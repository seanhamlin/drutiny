<?php

namespace Drutiny;

/**
 *
 */
class Context {
  protected $contexts = [];

  /**
   *
   */
  public function set($name, $value) {
    $this->contexts[$name] = $value;
    return $this;
  }

  /**
   *
   */
  public function __get($name) {
    if (isset($this->contexts[$name])) {
      return $this->contexts[$name];
    }
    throw new \Exception("Unknown context: $name");
  }

}
