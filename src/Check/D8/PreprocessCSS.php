<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "CSS aggregation",
 *  description = "With CSS optimization disabled, your website visitors are experiencing slower page performance and the server load is increased.",
 *  remediation = "Set the configuration object <code>system.performance</code> key <code>css.preprocess</code> to be <code>TRUE</code>.",
 *  success = "CSS aggregation is enabled.:fixups",
 *  failure = "CSS aggregation is not enabled.",
 *  exception = "Could not determine CSS aggregation setting.",
 *  supports_remediation = TRUE,
 * )
 */
class PreprocessCSS extends Check {

  /**
   * @inheritDoc
   */
  public function check() {
    return $this->context->drush->getConfig('system.performance', 'css.preprocess', TRUE);
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    $res = $this->context->drush->configSet('system.performance', 'css.preprocess', TRUE);
    return $res->isSuccessful();
  }

}
