<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Check\RemediableInterface;
use Drutiny\Sandbox\Sandbox;

/**
 * CSS aggregation.
 */
class PreprocessCSS extends Check implements RemediableInterface {

  /**
   * @inheritDoc
   */
  public function check(Sandbox $sandbox) {
    $config = $sandbox->drush(['format' => 'json'])
      ->configGet('system.performance', 'css.preprocess');

    return (bool) $config['system.performance:css.preprocess'];
  }

  /**
   * @inheritDoc
   */
  public function remediate(Sandbox $sandbox) {
    $sandbox->drush()
      ->configSet('-y', 'system.performance', 'css.preprocess', 1);

    return $this->check($sandbox);
  }

}
