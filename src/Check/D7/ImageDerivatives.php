<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Image derivative security",
 *  description = "Drupal core's Image module allows for the on-demand generation of image derivatives. This capability can be abused by requesting a large number of new derivatives which can fill up the server disk space, and which can cause a very high CPU load. Either of these effects may lead to the site becoming unavailable or unresponsive.",
 *  remediation = "Delete the variable <code>image_allow_insecure_derivatives</code>.",
 *  success = "Image derivative security is enabled.:fixups",
 *  failure = "Image derivative security is not enabled.",
 *  exception = "Could not determine image derivative settings.",
 *  supports_remediation = TRUE,
 * )
 */
class ImageDerivatives extends Check {

  /**
   * @inheritDoc
   * @see https://github.com/drupal/drupal/blob/7.x/modules/image/image.module#L821
   */
  public function check() {
    $image_allow_insecure_derivatives = (bool) $this->context->drush->getVariable('image_allow_insecure_derivatives', FALSE);
    return !$image_allow_insecure_derivatives;
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    $res = $this->context->drush->deleteVariable('image_allow_insecure_derivatives');
    return $res->isSuccessful();
  }

}
