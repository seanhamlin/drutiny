<?php

namespace Drutiny\Check;

use Symfony\Component\ClassLoader\ClassMapGenerator;
use Doctrine\Common\Annotations\AnnotationReader;

class Registry
{
  static protected $registry = array();

  /**
   * Retrieve a list of Check classes.
   */
  static public function load() {
    if (empty(self::$registry)) {
      $reader = new AnnotationReader();
      $map = ClassMapGenerator::createMap('src/Check');

      foreach ($map as $class => $filepath) {
        $reflect = new \ReflectionClass($class);
        if ($reflect->isAbstract()) {
          continue;
        }
        if (!$reflect->isSubClassOf('Drutiny\Check\Check')) {
          continue;
        }
        $info = $reader->getClassAnnotations($reflect);
        self::$registry[$class] = $info[0];
      }
    }
    return self::$registry;
  }
}

 ?>
