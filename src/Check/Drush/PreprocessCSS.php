<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "CSS aggregation",
 *  description = "With CSS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the variable <code>preprocess_css</code> to be <code>1</code>.",
 *  success = "CSS aggregation is enabled.",
 *  failure = "CSS aggregation is not enabled.",
 *  exception = "Could not determine CSS aggregation setting.",
 * )
 */
class PreprocessCss extends Check {
  public function check()
  {
    return (bool) (int) $this->context->drush->getVariable('preprocess_css', 0);
  }
}
