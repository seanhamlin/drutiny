<?php

namespace Drutiny\Profile;

use Drutiny\Api;
use Drutiny\Cache;
use Drutiny\Container;
use Drutiny\Profile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ProfileSourceLocalFs implements ProfileSourceInterface {

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
    $cache = Container::cache($this->getName())->getItem('profiles');
    if ($cache->isHit()) {
      return $cache->get();
    }

    $directories = array('.', getenv('HOME') . '/.drutiny/');
    $finder = new Finder();
    $finder->files()
      ->in($directories)
      ->name('*.profile.yml');

    $list = [];
    foreach ($finder as $file) {
      $name = str_replace('.profile.yml', '', pathinfo($file->getRealPath(), PATHINFO_BASENAME));
      $profile = Yaml::parse(file_get_contents($file->getRealPath()));
      $profile['filepath'] = $file->getRealPath();
      $profile['name'] = $name;
      unset($profile['format']);
      $list[$name] = $profile;
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
    return Profile::loadFromFile($definition['filepath']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight()
  {
    return -10;
  }
}
