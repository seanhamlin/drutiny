<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "JS aggregation",
 *  description = "With JS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the configuration object <code>system.performance</code> key <code>js.preprocess</code> to be <code>TRUE</code>.",
 *  success = "JS aggregation is enabled.:fixups",
 *  failure = "JS aggregation is not enabled.",
 *  exception = "Could not determine JS aggregation setting.",
 *  supports_remediation = TRUE,
 * )
 */
class PreprocessJs extends Check {
  public function check()
  {
    $fixups = [];
    $preprocess_js = $this->context->drush->getConfig('system.performance', 'js.preprocess', TRUE);

    if (!$preprocess_js && $this->context->autoRemediate) {
      $this->context->drush->configSet('system.performance', 'js.preprocess', TRUE);
      $fixups[] = 'This was auto remediated.';
      $preprocess_js = TRUE;
    }

    $this->setToken('fixups', ' ' . implode(', ', $fixups));
    return $preprocess_js;
  }
}
