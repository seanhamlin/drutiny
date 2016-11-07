<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Google analytics",
 *  description = "Tests to ensure the site is correctly configured google analytics.",
 *  remediation = "Fix the failures.",
 *  not_available = "Google analytics is not enabled.",
 *  success = "Google Analytics is configured correctly.",
 *  failure = "Google Analytics is not configured correctly. :errors",
 *  exception = "Could not determine Google Analytics settings.",
 * )
 */
class GA extends Check {
  public function check() {
    if (!$this->context->drush->moduleEnabled('googleanalytics')) {
      throw new DoesNotApplyException();
    }

    // Ensure the default tracker is set, as this will prevent pages from being
    // tracked.
    $googleanalytics_account = $this->context->drush->getVariable('googleanalytics_account', 'UA-');
    $googleanalytics_cache = (bool) $this->context->drush->getVariable('googleanalytics_cache', 0);
    $googleanalytics_codesnippet_after = $this->context->drush->getVariable('googleanalytics_codesnippet_after', '');

    $this->setToken('googleanalytics_account', $googleanalytics_account);
    $this->setToken('googleanalytics_cache', $googleanalytics_cache);
    $this->setToken('googleanalytics_codesnippet_after', $googleanalytics_codesnippet_after);

    $errors = [];
    $pattern = $this->getOption('account', '^UA-\d{7,8}-\d{1,2}$');
    if (!preg_match("#${pattern}#", $googleanalytics_account)) {
      $errors[] = 'Account does not match pattern - <code>googleanalytics_account</code> is set to <code>' . $googleanalytics_account . '</code>';
    }

    $cache = (bool) $this->getOption('cache', 0);
    if ($cache !== $googleanalytics_cache) {
      $errors[] = 'Caching is not correct - <code>googleanalytics_cache</code> is set to <code>' . ($googleanalytics_cache ? 'TRUE' : 'FALSE') . '</code>';
    }

    $codesnippet_after = trim($this->getOption('codesnippet_after', ''));
    if (!empty($codesnippet_after)) {
      if ($codesnippet_after !== $googleanalytics_codesnippet_after) {
        $errors[] = 'Code snippet after is not correct - <code>googleanalytics_codesnippet_after</code> is set to <code>' . $googleanalytics_codesnippet_after . '</code>';
      }
    }

    $this->setToken('errors', implode(', ', $errors));

    return empty($errors);
  }
}
