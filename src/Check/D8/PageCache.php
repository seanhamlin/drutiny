<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Check\RemediableInterface;
use Drutiny\Sandbox\Sandbox;

/**
 * Ensure you page cache expiry is set to an optimal level for best performance.
 */
class PageCache extends Check implements RemediableInterface {

  const DEFAULT_MAX_AGE = 3600;

  /**
   * @inheritDoc
   */
  public function check(Sandbox $sandbox) {
    $lifetime = $sandbox->getParameter('max_age', self::DEFAULT_MAX_AGE);

    $config = $sandbox->drush(['format' => 'json'])
      ->configGet('system.performance', 'cache.page.max_age');

    $max_age = $config['system.performance:cache.page.max_age'];
    $sandbox->setParameter('max_age_reading', $max_age);

    return $max_age >= $lifetime;
  }

  /**
   * @inheritDoc
   */
  public function remediate(Sandbox $sandbox) {
    $sandbox->drush()->configSet('-y', 'system.performance', 'cache.page.max_age', $sandbox->getParameter('max_age', self::DEFAULT_MAX_AGE));
    return $this->check($sandbox);
  }

}
