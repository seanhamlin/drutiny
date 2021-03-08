<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractComparison;

/**
 * Check a configuration is set correctly.
 */
class ConfigCheck extends AbstractComparison
{


    public function configure()
    {
           $this->addParameter(
               'collection',
               static::PARAMETER_OPTIONAL,
               'The collection the config belongs to.',
           );
        $this->addParameter(
            'key',
            static::PARAMETER_OPTIONAL,
            'The key the config belongs to.',
        );
        $this->addParameter(
            'value',
            static::PARAMETER_OPTIONAL,
            'The value to compare against the retrived value.',
        );
        $this->addParameter(
            'comp_type',
            static::PARAMETER_OPTIONAL,
            'The type of comparison to conduct. Defaults to equals. See Drutiny\Audit\AbstractComparison',
        );
        $this->setDeprecated();
    }

  /**
   * @inheritDoc
   */
    public function audit(Sandbox $sandbox)
    {
        $collection = $this->getParameter('collection');
        $key = $this->getParameter('key');
        $value = $this->getParameter('value');

        $config = $sandbox->drush([
        'format' => 'json',
        'include-overridden' => null,
        ])->configGet($collection, $key);
        $reading = $config[$collection . ':' . $key];

        $this->set('reading', $reading);

        return $this->compare($reading, $value, $sandbox);
    }
}
