<?php

namespace Drutiny;
use Symfony\Component\ClassLoader\ClassMapGenerator;

class CommandDiscovery
{

  static public function findCommands()
  {
    $map = ClassMapGenerator::createMap(__DIR__. '/Command');
    $commands = [];
    foreach ($map as $class => $filepath) {
      $reflection = new \ReflectionClass($class);
      if (!$reflection->isAbstract() && $reflection->isSubclassOf('Symfony\Component\Console\Command\Command')) {
        $commands[] = new $class();
      }
    }
    return $commands;
  }
}
