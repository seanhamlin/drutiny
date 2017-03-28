<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Page compression",
 *  description = "Drupal's Compress cached pages option (page_compression) can cause unexpected behavior when an external cache such as Varnish is employed, and typically provides no benefit. Therefore, Compress cached pages should be disabled.",
 *  remediation = "Set the variable <code>page_compression</code> to <code>0</code>.",
 *  success = "Compress cached pages is disabled.",
 *  failure = "Compress cached pages (page_compression) is enabled.",
 *  exception = "Could not determine status of page_compression: :exception.",
 *  supports_remediation = TRUE,
 * )
 */
class PageCompression extends Check {

  /**
   * @inheritDoc
   */
  public function check() {
    $page_compression = (bool) $this->context->drush->getVariable('page_compression', TRUE);
    $this->setToken('page_compression', $page_compression);
    return !$page_compression;
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    $res = $this->context->drush->setVariable('page_compression', 0);
    return $res->isSuccessful();
  }

}
