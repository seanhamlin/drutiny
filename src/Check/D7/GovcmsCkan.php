<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "govCMS CKAN",
 *  description = "Ensuring that the module is configured to talk to the production data endpoint and not some testing endpoint.",
 *  not_available = "govcms_ckan is not enabled.",
 *  remediation = "Move your CKAN dataset to <code>:desired</code> and then set the variable <code>govcms_ckan_endpoint_url</code> to point to it.",
 *  success = "govcms_ckan is correctly configured to talk to <code>:current</code>.",
 *  failure = "govcms_ckan is not configured correctly, currently talking to <code>:current</code>.",
 *  exception = "Could not determine govcms_ckan settings.",
 * )
 */
class GovcmsCkan extends Check {

  /**
   * @inheritDoc
   */
  public function check() {
    if (!$this->context->drush->moduleEnabled('govcms_ckan')) {
      return AuditResponse::AUDIT_NA;
    }

    $current = $this->context->drush->getVariable('govcms_ckan_endpoint_url', '');
    $this->desired = $this->getOption('endpoint', 'https://data.gov.au');
    $this->setToken('current', $current);
    $this->setToken('desired', $this->desired);

    return $current === $this->desired;
  }

}
