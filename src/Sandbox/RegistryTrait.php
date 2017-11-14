<?php

namespace Drutiny\Sandbox;

trait RegistryTrait {

  protected $callbackRegistry = [];

  public function registerMethod($name, \Closure $callback)
  {
    if (isset($this->callbackRegistry[$name])) {
      throw new \InvalidArgumentException("$name is already a registered callback in " . get_class($this));
    }
    $this->callbackRegistry[$name] = $callback;
    return $this;
  }

  public function __call($method, $args)
  {
    if (!isset($this->callbackRegistry[$method])) {
      throw new \ErrorException("Unknown method $method on " . get_class($this));
    }
    return call_user_func_array($this->callbackRegistry[$method], $args);
  }
}

 ?>
