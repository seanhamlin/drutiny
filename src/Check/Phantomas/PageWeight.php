<?php

namespace Drutiny\Check\Phantomas;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Page weight",
 *  description = "You should aim to keep your page weight as low as possible to ensure speedy download and rendering times for your site. This impacts not only your site's user experience but also it's SEO.",
 *  remediation = "Look to optimise the largest and slowest files.",
 *  success = "Page weight is smaller than <code>:max_size</code> MB. Actual size is <code>:value</code> MB. :biggest_response",
 *  warning = "Page weight is smaller than <code>:max_size</code> MB but larger than <code>:warning_size</code> MB. Actual size is <code>:value</code> MB. :biggest_response",
 *  failure = "Page weight is currently larger than <code>:max_size</code> MB. Actual size is <code>:value</code> MB. :biggest_response",
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

    // Find the largest asset if we are above the warning size.
    $biggestResponse = $this->context->phantomas->getOffender('biggestResponse');
    $this->setToken('biggest_response', 'The biggest response was <code>' . $biggestResponse[0] . '</code>.');

    if ($pageWeightInMB >= $max_size) {
      return AuditResponse::AUDIT_FAILURE;
    }
    if ($pageWeightInMB >= $warning_size) {
      return AuditResponse::AUDIT_WARNING;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
