<?php

namespace SiteAudit\Check\Phantomas;

use SiteAudit\Check\Check;
use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Page weight",
 *  description = "You should aim to keep your page weight as low as possible to ensure speedy download and rendering times for your site. This impacts not only your site's user experience but also it's SEO.",
 *  remediation = "Look to optimise the largest and slowest files.",
 *  success = "Page weight is smaller than <code>:max_size</code> MB. Actual size is <code>:value</code> MB.",
 *  warning = "Page weight is smaller than <code>:max_size</code> MB but larger than <code>:warning_size</code> MB. Actual size is <code>:value</code> MB.",
 *  failure = "Page weight is currently larger than <code>:max_size</code> MB. Actual size is <code>:value</code> MB.",
 *  exception = "Could not determine page weight.",
 * )
 */
class PageWeight extends Check {

  public function check()
  {
    $pageWeightBytes = $this->context->phantomas->getMetric('contentLength');
    $pageWeightInMB = (float) ($pageWeightBytes / (1024 * 1024));
    $pageWeightFriendly = sprintf('%0.2f', $pageWeightInMB);

    $max_size = (float) $this->getOption('max_size', 5);
    $warning_size = (float) $this->getOption('warning_size', 2);
    $this->setToken('max_size', $max_size);
    $this->setToken('warning_size', $warning_size);
    $this->setToken('value', $pageWeightFriendly);

    if ($pageWeightInMB >= $max_size) {
      return AuditResponse::AUDIT_FAILURE;
    }
    if ($pageWeightInMB >= $warning_size) {
      return AuditResponse::AUDIT_WARNING;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
