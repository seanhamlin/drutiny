<?php

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Search 404",
 *  description = "Search 404 can cause performance impacts to your site if it is enabled and set to automatically search upon encountering a 404. Also, when search404 issues a HTTP 302, and not a 404, this can confuse search engines.",
 *  remediation = "Set the variable <code>search404_skip_auto_search</code> to be <code>TRUE</code>, and the variable <code>search404_do_custom_search</code> to be <code>FALSE</code>.",
 *  not_available = "Search 404 module is disabled.",
 *  success = "Search 404 is set to not auto search and to produce an actual 404.",
 *  failure = "Search 404 is not configured correctly. :errors",
 *  exception = "Could not determine Search 404 setting.",
 * )
 */
class Search404 extends Check {
  public function check()
  {
    // If the module is disabled, then no search404.
    if ($this->context->drush->moduleEnabled('search404')) {

      // There is a variable that can skip automatic searching, which is
      // desirable from a performance perspective.
      $skip_auto_search = (bool) $this->context->drush->getVariable('search404_skip_auto_search', FALSE);
      $search404_do_custom_search = (bool) $this->context->drush->getVariable('search404_do_custom_search', FALSE);
      $search404_no_redirect = (bool) $this->context->drush->getVariable('search404_no_redirect', FALSE);

      $this->setToken('search404_skip_auto_search', $skip_auto_search ? 'TRUE' : 'FALSE');
      $this->setToken('search404_do_custom_search', $search404_do_custom_search ? 'TRUE' : 'FALSE');

      $errors = [];
      if (!$skip_auto_search) {
        $errors[] = 'Auto search is enabled - <code>search404_skip_auto_search</code> is set to <code>' . ($skip_auto_search ? 'TRUE' : 'FALSE') . '</code>';
      }
      if ($search404_do_custom_search) {
        $errors[] = 'Auto search is enabled with custom search - <code>search404_do_custom_search</code> is set to <code>' . ($search404_do_custom_search ? 'TRUE' : 'FALSE') . '</code>';
      }

      $this->setToken('errors', implode(', ', $errors));

      return empty($errors);
    }
    // If the module is not enabled, then this check does not apply.
    else {
      throw new DoesNotApplyException();
    }

    return TRUE;
  }
}
