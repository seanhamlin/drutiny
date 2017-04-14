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
    foreach (Registry::commands() as $class => $info) {
      $commands[] = new $class();
    }
    return $commands;
  }

}
