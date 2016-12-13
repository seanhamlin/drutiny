<?php

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Views SQL Signature",
 *  description = "Ensure that Views SQL queries contain a signature that will identify the view the SQL query came from. Useful for database performance debugging.",
 *  remediation = "Set the variable `views_sql_signature` to be `1`.",
 *  success = "Views SQL Signature is enabled.",
 *  failure = "Views SQL Signature is not enabled.",
 *  exception = "Could not determine Views SQL Signature setting.",
 * )
 */
class ViewsSqlSignature extends Check {
  public function check()
  {
    if (!$this->context->drush->moduleEnabled('views')) {
      throw new DoesNotApplyException("Views is not enabled on this site.");
    }
    $views_sql_signature = (bool) (int) $this->context->drush->getVariable('views_sql_signature', 0);
    return $views_sql_signature;
  }
}
