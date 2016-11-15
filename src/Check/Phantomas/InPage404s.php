<?php

namespace SiteAudit\Check\Phantomas;

use SiteAudit\Check\Check;
use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "In page 404s",
 *  description = "You should not have any broken assets on your page.",
 *  remediation = "Fix the 404s.",
 *  success = "No broken assets on the page (out of <code>:total</code> total requests).",
 *  failure = "Broken assets on the page (<code>:not_found</code> out of <code>:total</code> requests).",
 *  exception = "Could not determine if there are any in page 404s.",
 * )
 */
class InPage404s extends Check {

  public function check()
  {
    $not_found = (int) $this->context->phantomas->getMetric('notFound');
    $total = $this->context->phantomas->getMetric('requests');

    $this->setToken('total', $total);
    $this->setToken('not_found', $not_found);

    if ($not_found > 0) {
      return AuditResponse::AUDIT_FAILURE;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
