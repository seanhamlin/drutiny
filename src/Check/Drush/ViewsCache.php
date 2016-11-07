<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Views caching",
 *  description = "Having views caching can be great for performance.",
 *  remediation = "Enable views caching",
 *  success = "w00t.",
 *  failure = "boo.",
 *  exception = "Could not determine views caching settings :exception.",
 * )
 */
class ViewsCache extends Check {
  public function check() {

    $output = $this->context->drush->runScript('ViewsCache');
    var_dump($output);

    return TRUE;
  }
}
