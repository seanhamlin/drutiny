<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "CSS aggregation",
 *  description = "With CSS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the variable <code>preprocess_css</code> to be <code>1</code>.",
 *  success = "CSS aggregation is enabled.:fixups",
 *  failure = "CSS aggregation is not enabled.",
 *  exception = "Could not determine CSS aggregation setting.",
 *  supports_remediation = TRUE,
 * )
 */
class PreprocessCss extends Check {
  public function check()
  {
    $fixups = [];
    $preprocess_css = (bool) (int) $this->context->drush->getVariable('preprocess_css', 0);

    if (!$preprocess_css && $this->context->autoRemediate) {
      $this->context->drush->variableSet('preprocess_css', 1);
      $fixups[] = 'This was auto remediated.';
      $preprocess_css = TRUE;
    }

    $this->setToken('fixups', ' ' . implode(', ', $fixups) . '.');
    return $preprocess_css;
  }
}
