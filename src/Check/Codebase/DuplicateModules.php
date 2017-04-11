<?php

namespace Drutiny\Check\Codebase;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Duplicate modules",
 *  description = "Duplicate modules can cause a variety of strange behaviors should Drupal ever unexpectedly load the wrong version.",
 *  remediation = "Look to remove all duplicate modules, and standardise on a single copy in the codebase.",
 *  success = "No duplicate modules found.",
 *  failure = "Duplicate module:plural found - <ul><li><code>:issues</code></li></ul>",
 *  exception = "Could not determine if there were duplicate modules.",
 * )
 */
class DuplicateModules extends Check {

  /**
   * @inheritdoc
   */
  public function check() {
    $root = $this->context->drush->getCoreStatus('root');

    // Output is in the format:
    //
    // 2  ctools
    // 2  views
    //
    // @TODO base64 encode this command and run that on the server to avoid
    // having to escape everything many times.
    $command = "find {$root}/ -name '*.module' -type f | grep -Ev 'drupal_system_listing_(in)?compatible_test' | grep -oe '[^/]*\.module' | cut -d'.' -f1 | sort | uniq -c | sort -nr | awk '{if (\\$1 > 1) print \\$1 \\\"\t\\\" \\$2}'";
    $output = (string) $this->context->remoteExecutor->execute($command);

    if (empty($output)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    // Output from find is a giant string with newlines to seperate the modules.
    $rows = explode("\n", $output);
    $rows = array_map('trim', $rows);

    // Split by tab.
    $modules = [];
    foreach ($rows as $row) {
      $parts = explode("\t", $row);
      $count = (int) $parts[0];
      $module = trim($parts[1]);
      $modules[] = "{$module} - [{$count} times]";
    }

    $this->setToken('issues', implode('</code></li><li><code>', $modules));
    $this->setToken('plural', count($modules) > 1 ? 's' : '');

    return AuditResponse::AUDIT_FAILURE;
  }

}
