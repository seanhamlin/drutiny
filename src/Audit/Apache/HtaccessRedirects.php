<?php

namespace Drutiny\Audit\Apache;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * .htaccess redirects
 *
 * @Param(
 *  name = "max_redirects",
 *  description = "The maximum number of redirects to allow.",
 *  type = "integer",
 *  default = 10
 * )
 * @Token(
 *  name = "total_redirects",
 *  description = "The number of redirects counted.",
 *  type = "integer",
 *  default = 10
 * )
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
