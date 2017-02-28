<?php

namespace Drutiny\Check\Sample;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @CheckInfo(
 *  title = "Sample pass",
 *  description = "Sample pass descripion.",
 *  remediation = "Sample pass remediation.",
 *  success = "Sample pass success.",
 *  warning = "Sample pass warning.",
 *  failure = "Sample pass failure.",
 *  exception = "Sample pass exception.",
 *  testing = TRUE,
 * )
 */
class SamplePass extends Check {
  public function check()
  {
    return AuditResponse::AUDIT_SUCCESS;
  }
}
