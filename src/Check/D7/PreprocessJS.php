<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "JS aggregation",
 *  description = "With JS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the variable <code>preprocess_js</code> to be <code>1</code>.",
 *  success = "JS aggregation is enabled.:fixups",
 *  failure = "JS aggregation is not enabled.",
 *  exception = "Could not determine JS aggregation setting.",
 *  supports_remediation = TRUE,
 * )
 */
class PreprocessJS extends Check {
  public function check()
  {
    $fixups = [];
    $preprocess_js = (bool) (int) $this->context->drush->getVariable('preprocess_js', 0);

    if (!$preprocess_js && $this->context->autoRemediate) {
      $this->context->drush->variableSet('preprocess_js', 1);
      $fixups[] = 'This was auto remediated.';
      $preprocess_js = TRUE;
    }

    $this->setToken('fixups', ' ' . implode(', ', $fixups));
    return $preprocess_js;
  }
}
