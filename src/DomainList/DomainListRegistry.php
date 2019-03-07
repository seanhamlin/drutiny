<?php

namespace Drutiny\DomainList;

use Drutiny\Config;
use Doctrine\Common\Annotations\AnnotationReader;

class DomainListRegistry {
  public static function loadFromInput($source, $options = [])
  {
    $loaders = Config::get('DomainList');

    // Special case for default DomainListYamlFile.
    if (file_exists($source)) {
      $options['filepath'] = $source;
      return new $loaders['YamlFile']($options);
    }

    if (!isset($loaders[$source])) {
      throw new \Exception("No such DomainList loader known: $source.");
    }

    return new $loaders[$source]($options);
  }

  public static function getOptions($dl)
  {
    $loaders = Config::get('DomainList');
    $class = $loaders[$dl];

    $reflect = new \ReflectionClass($class);
    $reader = new AnnotationReader();
    if (!$reflect->implementsInterface('\Drutiny\DomainList\DomainListInterface')) {
      throw new \InvalidArgumentException("$class Does not implement Drutiny\DomainListInterface.");
    }
    return array_filter($reader->getClassAnnotations($reflect), function ($annotation) {
      return $annotation instanceof \Drutiny\Annotation\Param;
    });
  }
}
