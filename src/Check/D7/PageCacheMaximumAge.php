<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Page cache",
 *  description = "With a page cache max age set to less than 5 minutes, the server has to frequently regenerate pages which can decrease your site's performance.",
 *  remediation = "Set the variable <code>page_cache_maximum_age</code> to be greater than <code>300</code>.",
 *  success = "Page cache max-age is set above <code>:cache</code> seconds. Currently set to <code>:page_cache_maximum_age</code>.",
 *  failure = "Page cache max-age is not set above <code>:cache</code> seconds. Currently set to <code>:page_cache_maximum_age</code>.",
 *  exception = "Could not determine page cache max-age.",
 * )
 */
class PageCacheMaximumAge extends Check {

  /**
   *
   */
  public function check() {
    $page_cache_maximum_age = (int) $this->context->drush->getVariable('page_cache_maximum_age', 0);
    $this->setToken('page_cache_maximum_age', $page_cache_maximum_age);
    return ($page_cache_maximum_age >= $this->getOption('cache', 300));
  }

}
