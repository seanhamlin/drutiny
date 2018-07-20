<?php

namespace Drutiny\PolicySource;

use Drutiny\Api;
use Drutiny\Cache;
use Drutiny\Container;
use Drutiny\Policy;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class LocalFs implements PolicySourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'localfs';
  }

  /**
   * {@inheritdoc}
   */
  public function getList()
  {
    $cache = Container::cache($this->getName())->getItem('list');
    if ($cache->isHit()) {
      return $cache->get();
    }
    $finder = new Finder();
    $finder->files()
      ->in('.')
      ->name('*.policy.yml');

    $list = [];
    foreach ($finder as $file) {
      $policy = Yaml::parse(file_get_contents($file->getRealPath()));
      $policy['filepath'] = $file->getRealPath();
      $list[$policy['name']] = $policy;
    }
    Container::cache($this->getName())->save(
      $cache->set($list)->expiresAfter(3600)
    );
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition)
  {
    return new Policy($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight()
  {
    return -10;
  }
}
