<?php

namespace Drutiny\Check\Sample;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Sample failure",
 *  description = "Sample failure descripion.",
 *  remediation = "Sample failure remediation.",
 *  success = "Sample failure success.",
 *  warning = "Sample failure warning.",
 *  failure = "Sample failure failure.",
 *  exception = "Sample failure exception.",
 *  testing = TRUE,
 * )
 */
class SampleFailure extends Check {

  /**
   *
   */
  public function check() {
    return AuditResponse::AUDIT_FAILURE;
  }

}
