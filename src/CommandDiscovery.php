<?php

namespace Drutiny;

/**
 *
 */
class CommandDiscovery {

  /**
   *
   */
  public static function findCommands() {
    $commands = [];
    foreach ((new Registry())->commands() as $class_name) {
      $commands[] = new $class_name();
    }
    return $commands;
  }

}
