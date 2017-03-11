<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Views caching",
 *  description = "Having views caching can be great for performance.",
 *  remediation = "Enable views caching",
 *  success = "w00t.",
 *  failure = "boo.",
 *  exception = "Could not determine views caching settings :exception.",
 * )
 */
class ViewsCache extends Check {

  /**
   *
   */
  public function check() {

    $output = $this->context->drush->runScript('ViewsCache');
    var_dump($output);

    return TRUE;
  }

}
