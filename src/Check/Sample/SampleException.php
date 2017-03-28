<?php

namespace Drutiny\Check\Sample;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Sample exception",
 *  description = "Sample exception descripion.",
 *  remediation = "Sample exception remediation.",
 *  success = "Sample exception success.",
 *  warning = "Sample exception warning.",
 *  failure = "Sample exception failure.",
 *  exception = "Sample exception exception.",
 *  testing = TRUE,
 * )
 */
class SampleException extends Check {

  /**
   * @inheritDoc
   */
  public function check() {
    throw new \Exception('Sample exception text');
  }

}
