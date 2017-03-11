<?php

namespace Drutiny\Check\Phantomas;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "In page 404s",
 *  description = "You should not have any broken assets on your page.",
 *  remediation = "Fix the 404s.",
 *  success = "No broken assets on the page (out of <code>:total</code> total requests).",
 *  failure = "Broken assets on the page (<code>:not_found</code> out of <code>:total</code> requests). :not_found_assets.",
 *  exception = "Could not determine if there are any in page 404s.",
 * )
 */
class InPage404s extends Check {

  /**
   *
   */
  public function check() {
    $not_found = (int) $this->context->phantomas->getMetric('notFound');
    $total = $this->context->phantomas->getMetric('requests');

    $this->setToken('total', $total);
    $this->setToken('not_found', $not_found);

    if ($not_found > 0) {
      // Find the broken assets.
      $notFound = $this->context->phantomas->getOffender('notFound');
      $this->setToken('not_found_assets', 'The broken assets are <code>' . implode('</code>, <code>', $notFound) . '</code>');
      return AuditResponse::AUDIT_FAILURE;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }

}
