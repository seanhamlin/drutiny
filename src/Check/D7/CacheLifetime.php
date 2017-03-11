<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Internal cache",
 *  description = "The minimum cache lifetime is equivalent to setting the <code>cache_lifetime</code> Drupal variable. It prevents Drupal from clearing page and block caches after changes are made to nodes or blocks, for a set period of time. This can cause unexpected behavior when editing content or when an external cache such as a CDN or Varnish is employed.",
 *  remediation = "Set the variable <code>cache_lifetime</code> to be <code>0</code>.",
 *  success = "Internal cache is disabled.",
 *  failure = "Internal cache is enabled. Currently set to <code>:cache_lifetime</code> seconds.",
 *  exception = "Could not determine internal cache settings.",
 * )
 */
class CacheLifetime extends Check {

  /**
   *
   */
  public function check() {
    // @see https://github.com/drupal/drupal/blob/7.x/modules/system/system.admin.inc#L1716
    $cache_lifetime = (int) $this->context->drush->getVariable('cache_lifetime', 0);
    $this->setToken('cache_lifetime', $cache_lifetime);
    return $cache_lifetime === 0;
  }

}
