<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Page Cache",
 *  description = "Ensure you page cache expiry is set to an optimal level for best performance.",
 *  remediation = "Set the configuration object <code>system.performance</code> key <code>cache.page.max_age</code> to :page_cache.",
 *  success = "Page cache is configured correctly.",
 *  failure = "Page cache settings (cache.page.max_age) are not configured for optimal performance. Setting is :page_cache_lifetime seconds.",
 *  exception = "Could not determine page cache settings.",
 *  supports_remediation = TRUE,
 * )
 */
class PageCache extends Check {

  /**
   * @inheritDoc
   */
  public function check() {
    $lifetime = $this->getOption('page_cache', 3600);
    $setting = $this->context->drush->getConfig('system.performance', 'cache.page.max_age');
    $this->setToken('page_cache_lifetime', $setting);
    return $setting > $lifetime;
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    $lifetime = $this->getOption('page_cache', 3600);
    $res = $this->context->drush->configSet('system.performance', 'cache.page.max_age', $lifetime);
    return $res->isSuccessful();
  }

}
