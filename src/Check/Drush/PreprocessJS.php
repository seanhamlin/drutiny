<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 * title = "JS aggregation",
 * description = "With JS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 * remediation = "Set the variable <code>preprocess_js</code> to be <code>1</code>.",
 * success = "JS aggregation is enabled.",
 * failure = "JS aggregation is not enabled.",
 * exception = "Could not determine JS aggregation setting.",
 * )
 */
class PreprocessJS extends Check {
  public function check()
  {
    return (bool) (int) $this->context->drush->getVariable('preprocess_js', 0);
  }
}
