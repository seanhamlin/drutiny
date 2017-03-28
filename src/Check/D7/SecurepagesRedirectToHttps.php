<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Executor\DoesNotApplyException;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Securepages redirect to HTTPS",
 *  description = "The securepages module redirects the user to HTTPS given certain conditions. This check ensures that all traffic is sent to HTTPS.",
 *  remediation = "Set <code>securepages_enable</code> to <code>1</code>, <code>securepages_switch</code> to <code>0</code>, <code>securepages_secure</code> to <code>1</code>, <code>securepages_pages</code> to <code>*</code>.",
 *  success = "Securepages is redirectly all requests to HTTPS.",
 *  failure = "Securepages is not configured correctly to redirect all requests to HTTPS.",
 *  exception = "Could not determine securepages settings.",
 * )
 */
class SecurepagesRedirectToHttps extends Check {

  /**
   *
   */
  public function check() {
    if ($this->context->drush->moduleEnabled('securepages')) {
      $securepages_enable = (bool) $this->context->drush->getVariable('securepages_enable', 0);
      $securepages_switch = (bool) $this->context->drush->getVariable('securepages_switch', 0);
      $securepages_secure = (bool) $this->context->drush->getVariable('securepages_secure', 1);
      $securepages_pages = $this->context->drush->getVariable('securepages_pages', '');

      // Securepages must:
      // * be enabled
      // * not switch back to HTTP
      // * make secure only the listed pages
      // * the listed pages is '*'.
      if ($securepages_enable && !$securepages_switch && $securepages_secure && $securepages_pages === '*') {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    throw new DoesNotApplyException('securepages is not enabled.');
  }

}
