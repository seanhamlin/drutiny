<?php

namespace Drutiny\Check\Sample;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @CheckInfo(
 *  title = "Sample failure",
 *  description = "Sample failure descripion.",
 *  remediation = "Sample failure remediation.",
 *  success = "Sample failure success.",
 *  failure = "Sample failure failure.",
 *  exception = "Sample failure exception.",
 * )
 */
class SampleFailure extends Check {
  public function check()
  {
    return AuditResponse::AUDIT_FAILURE;
  }
}
