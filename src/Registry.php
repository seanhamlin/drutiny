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
  public function targets() {
    $targets = [];
    foreach ($this->config()->Target as $class) {
      $info = $this->loadClassInfo($class, '\Drutiny\Target\Target');
      $info->class = $class;
      $targets[$info->name] = $info;
    }
    return $targets;
  }

  public function getTargetClass($name) {
    $targets = $this->targets();
    if (!isset($targets[$name])) {
      throw new \InvalidArgumentException("Cannot find a registered target with the name: $name.");
    }
    return $targets[$name]->class;
  }

  protected function config()
  {
    $finder = new Finder();
    $finder->files()
      ->in('.')
      ->name('drutiny.config.yml');

    $config = [];
    foreach ($finder as $file) {
      $config[] = Yaml::parse(file_get_contents($file->getRealPath()));
    }
    $config = call_user_func_array('array_merge_recursive', $config);
    return (object) $config;
  }

  protected function loadClassInfo($class, $type)
  {
    $reflect = new \ReflectionClass($class);
    $reader = new AnnotationReader();
    if ($reflect->isAbstract()) {
      throw new \InvalidArgumentException("$class: Annotations are not supported on abstract classes.");
    }
    if (!$reflect->isSubClassOf($type)) {
      throw new \InvalidArgumentException("$class is not of type $type.");
    }
    $info = $reader->getClassAnnotations($reflect);
    $info = empty($info) ? new \stdClass : $info[0];

    $info->class = $class;
    return $info;
  }

  /**
   *
   */
  public function policies() {
    static $registry;

    if ($registry) {
      return $registry;
    }

    $finder = new Finder();
    $finder->files()
      ->in('.')
      ->name('*.policy.yml');

    $registry = [];
    foreach ($finder as $file) {
      $policy = Yaml::parse(file_get_contents($file->getRealPath()));
      $registry[$policy['name']] = new Policy($policy);
    }
    return $registry;
  }

  /**
   *
   */
  public function commands() {
    $commands = [];
    foreach ($this->config()->Command as $class) {
      $info = $this->loadClassInfo($class, '\Symfony\Component\Console\Command\Command');
      $commands[] = $info->class;
    }
    return $commands;
  }

  /**
   *
   */
  public function profiles() {
    static $registry;

    if (!empty($registry)) {
      return $registry;
    }

    $finder = new Finder();
    $finder->files();
    $finder->in('.');

    $finder->name('*.profile.yml');

    $registry = [];
    foreach ($finder as $file) {
      $profile = Yaml::parse(file_get_contents($file->getRealPath()));
      $profile['name'] = str_replace('.profile.yml', '', $file->getFilename());
      $registry[$profile['name']] = new ProfileInformation($profile);
    }
    return $registry;
  }

}
