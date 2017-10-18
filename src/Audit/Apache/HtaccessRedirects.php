<?php

namespace Drutiny\Audit\Apache;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;

/**
 * .htaccess redirects
 */
class HtaccessRedirects extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

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
