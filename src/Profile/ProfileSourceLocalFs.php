<?php

namespace Drutiny\Profile;

use Drutiny\Api;
use Drutiny\Cache;
use Drutiny\Config;
use Drutiny\Container;
use Drutiny\Profile;
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

    $finder = Config::getFinder()->name('*.profile.yml');

    $list = [];
    foreach ($finder as $file) {
      $filename = $file->getPathname();
      $name = str_replace('.profile.yml', '', pathinfo($filename, PATHINFO_BASENAME));
      $profile = Yaml::parse($file->getContents());
      $profile['filepath'] = $filename;
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
