<?php

namespace Drutiny\Target;

use Drutiny\Target\Service\DrushService;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Target for parsing Drush aliases.
 */
class DrushTarget extends Target implements TargetInterface, TargetSourceInterface
{
  /**
   * {@inheritdoc}
   */
  public function getId():string
  {
    return $this['drush.alias'];
  }

  /**
   * @inheritdoc
   * Implements Target::parse().
   */
    public function parse($alias):TargetInterface
    {

        $this['drush.alias'] = $alias;

        $status_cmd = 'drush site:alias $DRUSH_ALIAS --format=json';
        $drush_properties = $this['service.local']->run($status_cmd, function ($output) use ($alias) {
          $json = json_decode($output, true);
          $index = substr($alias, 1);
          return $json[$index] ?? array_shift($json);
        });

        $this['drush']->add($drush_properties);

        // Provide a default URI if none already provided.
        if (isset($drush_properties['uri']) && !$this->hasProperty('uri')) {
          $this->setUri($drush_properties['uri']);
        }

        $this->buildAttributes();
        return $this;
    }

    public function buildAttributes() {
        $service = new DrushService($this['service.exec']);

        if ($url = $this->getUri()) {
          $service->setUrl($url);
        }

        try {
          $status = $service->status(['format' => 'json'])
             ->run(function ($output) {
               return json_decode($output, TRUE);
             });

          foreach ($status as $key => $value) {
            $this['drush.'.$key] = $value;
          }

          $this['service.drush'] = $service;

          $version = $this['service.exec']->run('php -v | head -1 | awk \'{print $2}\'');
          $this['php_version'] = trim($version);

          return $this;
        }
        catch (ProcessFailedException $e) {
          throw new InvalidTargetException($e->getMessage());
        }

        foreach ($status as $key => $value) {
          $this['drush.'.$key] = $value;
        }

        $this['service.drush'] = $service;

        $version = $this['service.exec']->run('php -v | head -1 | awk \'{print $2}\'');
        $this['php_version'] = trim($version);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUri(string $uri)
    {
      parent::setUri($uri);
      // Rebuild the drush attributes.
      return $this->buildAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTargets():array
    {
      $aliases = $this['service.local']->run('drush site-alias --format=json', function ($output) {
        return json_decode($output, true);
      });
      $valid = array_filter(array_keys($aliases), function ($a) {
        return strpos($a, '.') !== FALSE;
      });

      $targets = [];
      foreach ($valid as $name) {
        $alias = $aliases[$name];
        $targets[] = [
          'id' => $name,
          'uri' => $alias['uri'] ?? '',
          'name' => $name
        ];
      }
      return $targets;
    }
}
