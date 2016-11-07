<?php

namespace SiteAudit\Check\Acsf;

use SiteAudit\Check\Check;
use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Executor\DoesNotApplyException;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "ACSF theme security",
 *  description = "Some basic checks to ensure that the theme is not doing any seriously bad things. Note this is not supposed to be perfect, but used as an aid in code review.",
 *  remediation = "Look to shift the functionality to a module, and get it out of the theme.",
 *  success = "No security issues found.",
 *  failure = "Security issue:plural :prefix found - <br><br><code>:issues</code>.",
 *  exception = "Could not determine theme security.",
 *  not_available = "No custom theme is linked.",
 * )
 */
class ThemeSecurity extends Check {

  public function check()
  {
    $root = $this->context->drush->getCoreStatus('root');
    $site = $this->context->drush->getCoreStatus('site');

    $look_out_for = [
      "_POST",
      "exec(",
      "db_query",
      "db_select",
      "db_merge",
      "db_update",
      "db_write_record",
      "->query",
      "drupal_http_request",
      "curl_init",
    ];

    // This command is probably more complex then it should be due to wanting to
    // remove the main theme folder prefix.
    //
    // Yields something like:
    //
    // ./zen/template.php:159:    $path = drupal_get_path_alias($_GET['q']);
    // ./zen/template.php:162:    $arg = explode('/', $_GET['q']);
    $command = "if [ -d '{$root}/{$site}/themes/site/' ]; then cd {$root}/{$site}/themes/site/ ; grep -nrI --include=*.php --include=*.inc '" . implode('\|', $look_out_for) . "' . || echo 'nosecissues' ; else echo 'nope'; fi";
    $output = (string) $this->context->remoteExecutor->execute($command);

    // The ACSF site can have no custom theme repo linked, in which case we
    // will see a "du: cannot access ... No such file or directory" error
    // response. For now, we force this command to not fail using an or
    // statement.
    if (preg_match('/^nope$/', $output)) {
      throw new DoesNotApplyException();
    }

    if (preg_match('/^nosecissues/', $output)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    // Output from find is a giant string with newlines to seperate the files.
    $rows = explode("\n", $output);
    $rows = array_map('trim', $rows);
    $rows = array_map('strip_tags', $rows);
    $rows = array_filter($rows);

    // Ignore certain paths that are known to be safe.
    foreach ($rows as $index => $row) {
      if (strpos($row, './bootstrap/includes/cdn.inc:') === 0) {
        unset($rows[$index]);
      }
      if (strpos($row, './bootstrap/includes/bootstrap.drush.inc:') === 0) {
        unset($rows[$index]);
      }
    }

    $this->setToken('issues', implode('</code>, <br><code>', $rows));
    $this->setToken('plural', count($rows) > 1 ? 's' : '');
    $this->setToken('prefix', count($rows) > 1 ? 'were' : 'was');

    return count($rows) === 0;
  }
}
