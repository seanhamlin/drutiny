<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "CSS aggregation",
 *  description = "With CSS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the configuration object <code>system.performance</code> key <code>css.preprocess</code> to be <code>TRUE</code>.",
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
    $preprocess_css = $this->context->drush->getConfig('system.performance', 'css.preprocess', TRUE);

    if (!$preprocess_css && $this->context->autoRemediate) {
      $this->context->drush->configSet('system.performance', 'css.preprocess', TRUE);
      $fixups[] = 'This was auto remediated.';
      $preprocess_css = TRUE;
    }

    $this->setToken('fixups', ' ' . implode(', ', $fixups));
    return $preprocess_css;
  }
}
