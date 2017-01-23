<?php
/**
 * @file
 * Contains SiteAudit\Check\D7\ViewsPagination
 */

namespace SiteAudit\Check\D7;

use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;


/**
 * @CheckInfo(
 *   title = "Views Pagination",
 *   description = "Ensure views pagination is not over a threshold",
 *   remediation = "Change the following views pagination settings to below :threshold: <ul><li>:error</li></ul>",
 *   success = "Found <code>:total</code> view:plural that are correctly paginating results.",
 *   failure = "Found <code>:error_count</code> view:plural that pagination exceeds threshold.",
 *   exception = "Error finding views",
 *   not_available = "No views found.",
 * )
 */
class ViewsPagination extends Check {
  public function check() {

    if (!$this->context->drush->moduleEnabled('views')) {
      return AuditResponse::AUDIT_NA;
    }

    $valid = 0;
    $errors = [];

    // View settings are set per display so we need to query the views display table.
    $views = $this->context->drush->sqlQuery("SELECT vd.vid, vd.display_title, vd.display_options, vv.name, vv.human_name FROM {views_display} vd JOIN {views_view} vv ON vv.vid = vd.vid");

    foreach ($views as $view) {
      list($display_id, $display_name, $display_options, $view_machine_name, $view_name) = explode("\t", $view);
      $display_options = unserialize($display_options);

      if (empty($display_options['pager']['options']['items_per_page'])) {
        continue;
      }

      if ($display_options['pager']['options']['items_per_page'] > $this->getOption('threshold', 30)) {
        $errors[] = "$view_name <i>[$display_name]</i> is displaying <code>{$display_options['pager']['options']['items_per_page']}</code>";
        continue;
      }

      $valid++;
    }

    $this->setToken('total', $valid);
    $this->setToken('plural', $valid > 1 ? 's' : '');
    $this->setToken('error', implode('</li><li>',  $errors));
    $this->setToken('threshold', $this->getOption('threshold', 30));
    $this->setToken('error_count', count($errors));

    return empty($errors) ? AuditResponse::AUDIT_SUCCESS : AuditResponse::AUDIT_FAILURE;
  }
}
