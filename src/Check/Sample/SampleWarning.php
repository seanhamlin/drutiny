<?php

namespace Drutiny\Check\Sample;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @CheckInfo(
 *  title = "Sample warning",
 *  description = "Sample warning descripion.",
 *  remediation = "Sample warning remediation.",
 *  success = "Sample warning success.",
 *  warning = "Sample warning warning.",
 *  failure = "Sample warning failure.",
 *  exception = "Sample warning exception.",
 *  testing = TRUE,
 * )
 */
class SampleWarning extends Check {
  public function check()
  {
    return AuditResponse::AUDIT_WARNING;
  }
}
