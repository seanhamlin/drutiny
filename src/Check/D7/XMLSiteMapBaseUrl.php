<?php

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "XML sitemap base URL",
 *  description = "The XML sitemap module adds a sitemap on the URL <code>/sitemap.xml</code>. If not properly configured, the sitemap will point to an incorrect or possibly broken site.",
 *  remediation = "Set the variable <code>xmlsitemap_base_url</code> to be the production www URL. e.g. <code>'https://www.govcms.gov.au'</code>. Note there is no trailing slash.",
 *  not_available = "XML sitemap module is disabled.",
 *  success = "XML sitemap base URL is set correctly, currently <code>:base_url</code>.",
 *  failure = "XML sitemap base URL is not correct, currently it is <code>:base_url</code>.",
 *  exception = "Could not determine XML sitemap base URL setting.",
 * )
 */
class XMLSiteMapBaseUrl extends Check {
  public function check()
  {
    // If the module is disabled, then no xmlsitemap.
    if ($this->context->drush->moduleEnabled('xmlsitemap')) {

      // This defaults to $GLOBALS['base_url'] which is bad.
      $base_url = (string) $this->context->drush->getVariable('xmlsitemap_base_url', '');
      if (empty($base_url)) {
        $this->setToken('base_url', '[empty]');
        return FALSE;
      }

      $this->setToken('base_url', $base_url);
      $pattern = $this->getOption('pattern', '^https?://.+$');
      if (!preg_match("#${pattern}#", $base_url)) {
        return FALSE;
      }
    }
    // If the module is not enabled, then this check does not apply.
    else {
      throw new DoesNotApplyException();
    }

    return TRUE;
  }
}
