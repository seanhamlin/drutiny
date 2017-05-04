<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Driver\DrushFormatException;
use Drutiny\Check\RemediableInterface;

/**
 * Check a configuration is set correctly.
 */
class ConfigCheck extends Check implements RemediableInterface {

  /**
   * @inheritDoc
   */
  public function check(Sandbox $sandbox) {
    $collection = $sandbox->getParameter('collection');
    $key = $sandbox->getParameter('key');
    $value = $sandbox->getParameter('value');

    $config = $sandbox->drush([
      'format' => 'json',
      'include-overridden' => NULL,
      ])->configGet($collection, $key);
    $reading = $config[$collection . ':' . $key];

    $sandbox->setParameter('reading', $reading);

    $comp_type = $sandbox->getParameter('comp_type', '===');
    $sandbox->logger()->info('Comparative config values: ' . var_export([
      'reading' => $reading,
      'value' => $value,
      'expression' => 'reading ' . $comp_type . ' value',
    ], TRUE));

    switch ($comp_type) {
      case 'lt':
      case '<':
        return $reading < $value;
      case 'gt':
      case '>':
        return $reading > $value;
      case 'lte':
      case '<=':
        return $reading <= $value;
      case 'gte':
      case '>=':
        return $reading >= $value;
      case 'ne':
      case '!=':
        return $reading != $value;
      case 'nie':
      case '!==':
        return $reading !== $value;
      case 'matches':
      case '~':
        return strpos($reading, $value) !== FALSE;
      case 'equal':
      case '==':
        return $value == $reading;
      case 'identical':
      case '===':
      default:
        return $value === $reading;
    }
  }

  /**
   * @inheritDoc
   */
  public function remediate(Sandbox $sandbox) {
    $collection = $sandbox->getParameter('collection');
    $key = $sandbox->getParameter('key');
    $value = $sandbox->getParameter('value');
    $sandbox->drush()->configSet($collection, $key, $value);
    return $this->check($sandbox);
  }

}
