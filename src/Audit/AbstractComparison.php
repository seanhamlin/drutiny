<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

abstract class AbstractComparison extends Audit {

  protected function compare($reading, $value, Sandbox $sandbox) {
    $comp_type = $sandbox->getParameter('comp_type', '==');
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
      case 'identical':
      case '===':
        return $value === $reading;
      case 'equal':
      case '==':
      default:
        return $value == $reading;
    }
  }
}

 ?>
