<?php

namespace Drutiny\Check\Apache;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;

/**
 * .htaccess redirects
 */
class HtaccessRedirects extends Check {

  /**
   *
   */
  public function check(Sandbox $sandbox) {

    $patterns = array(
      'RedirectPermanent',
      'Redirect(Match)?.*?(301|permanent) *$',
      'RewriteRule.*\[.*R=(301|permanent).*\] *$',
    );
    $regex = '^ *(' . implode('|', $patterns) . ')';
    $command = "grep -Ei '${regex}' %docroot%/.htaccess | wc -l";

    $total_redirects = (int) $sandbox->exec($command);

    $sandbox->setParameter('total_redirects', $total_redirects);

    return $total_redirects < $sandbox->getParameter('max_redirects', 10);
  }

}
