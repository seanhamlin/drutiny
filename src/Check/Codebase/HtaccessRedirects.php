<?php

namespace SiteAudit\Check\Codebase;

use SiteAudit\Annotation\CheckInfo;
use SiteAudit\Check\Check;

/**
 * @CheckInfo(
 *  title = ".htaccess redirects",
 *  description = "When there are a large number of redirects in the <code>.htaccess</code> file they are all required to be loaded at run time during every request as Apache needs to analyze the contents so that it can make appropriate decisions about how to process the application and incoming requests. Redirect rules should be refactored to take advantage of regular expressions if possible. Otherwise the redirect module should be added to the site and all of the redirects in the <code>.htaccess</code> file should be moved into the Drupal site. Although these redirects will then require a Drupal bootstrap in order to fulfill the request, Varnish will be able to cache the redirect once it has been made once as long as there is a maximum age set on the site.",
 *  remediation = "Reduce the number of redirects in the <code>.htaccess</code> file.",
 *  success = ".htaccess redirects less than <code>:max_redirects</code> redirects, actual value <code>:output</code> redirect(s).",
 *  failure = ".htaccess redirects less than <code>:max_redirects</code> redirects, actual value <code>:output</code> redirect(s).",
 *  exception = "Could not determine number of redirects in <code>.htaccess</code> file.",
 * )
 */
class HtaccessRedirects extends Check {
  public function check()
  {
    $context = $this->context;
    $patterns = array(
      'RedirectPermanent',
      'Redirect(Match)?.*?(301|permanent) *$',
      'RewriteRule.*\[.*R=(301|permanent).*\] *$'
    );
    $regex = '^ *(' . implode('|', $patterns) . ')';
    $command = "grep -Ei '${regex}' {$context->config['root']}/.htaccess | wc -l";
    $output = (int) (string) $context->remoteExecutor->execute($command);

    $this->setToken('output', $output);

    return $output < $this->getOption('max_redirects', 10);
  }
}
