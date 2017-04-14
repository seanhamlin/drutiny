<?php

namespace Drutiny;

use Symfony\Component\ClassLoader\ClassMapGenerator;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class Registry {

  const CHECK_DIRECTORY = 'src/Check';

  /**
   * Retrieve a list of Check classes.
   */
  public static function load($path, $type, $key_by = 'class') {
    $registry = [];
    $reader = new AnnotationReader();
    $map = ClassMapGenerator::createMap($path);

    foreach ($map as $class => $filepath) {
      $reflect = new \ReflectionClass($class);
      if ($reflect->isAbstract()) {
        continue;
      }
      if (!$reflect->isSubClassOf($type)) {
        continue;
      }
      $info = $reader->getClassAnnotations($reflect);

      if ($key_by == "class") {
        $registry[$class] = $class;
      }
      else {
        $info[0]->class = $class;
        $registry[$info[0]->{$key_by}] = $info[0];
      }
    }
    return $registry;
  }

  /**
   *
   */
  public static function targets() {
    return self::load('src/Target', 'Drutiny\Target\Target', 'name');
  }

  /**
   *
   */
  public static function checks() {
    $finder = new Finder();
    $finder->files()
      ->in(self::CHECK_DIRECTORY)
      ->name('*.yml');

    $registry = [];
    foreach ($finder as $file) {
      $check = Yaml::parse(file_get_contents($file->getRealPath()));
      $check['name'] = str_replace('.yml', '', $file->getFilename());
      $registry[$check['name']] = new CheckInformation($check);
    }
    return $registry;
  }

  /**
   *
   */
  public static function commands() {
    return self::load('src/Command', 'Symfony\Component\Console\Command\Command');
  }

  /**
   *
   */
  public static function profiles() {
    $finder = new Finder();
    $finder->files()
      ->in('profiles')
      ->name('*.profile.yml');

    $registry = [];
    foreach ($finder as $file) {
      $profile = Yaml::parse(file_get_contents($file->getRealPath()));
      $profile['name'] = str_replace('.profile.yml', '', $file->getFilename());
      $registry[$profile['name']] = new ProfileInformation($profile);
    }
    return $registry;
  }

}
