<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Securepages basepath",
 *  description = "The securepages module redirects the user to HTTPS given certain conditions. It is known to cause issues when you redirect to the production domain in all cases (e.g. you are trying to test on a test version of the site).",
 *  remediation = "Delete the 2 variables <code>securepages_basepath</code> and <code>securepages_basepath_ssl</code>, or set them to a blank string.",
 *  success = "Securepages basepath is configured correctly",
 *  failure = "Securepages basepath is not configured correctly",
 *  exception = "Could not determine securepages settings.",
 * )
 */
class SecurepagesBasepath extends Check {
  public function check()
  {
    // If the module is disabled, then no shield.
    if ($this->context->drush->moduleEnabled('securepages')) {
      // Shield must be enabled, defaults to on.
      $basepath = $this->context->drush->getVariable('securepages_basepath', '');
      $basepath_ssl = $this->context->drush->getVariable('securepages_basepath_ssl', '');
      if (empty($basepath) && empty($basepath_ssl)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }
}
