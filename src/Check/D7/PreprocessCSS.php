<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

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
class PreprocessCSS extends Check {

  /**
   * @inheritDoc
   */
  public function check()
  {
    return (bool) (int) $this->context->drush->getVariable('preprocess_css', 0);
  }

  /**
   * @inheritDoc
   */
  public function remediate()
  {
    $res = $this->context->drush->setVariable('preprocess_css', 1);
    return $res->isSuccessful();
  }
}
