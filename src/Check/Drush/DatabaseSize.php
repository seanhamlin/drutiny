<?php

namespace Drutiny\Check\Drush;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Database size",
 *  description = "Large databases can negatively impact your production site, and slow down things like database dumps.",
 *  remediation = "Find out what tables are the largest and see what can be done to make them smaller.",
 *  success = "The size of the database <code>:db</code> is good, currently <code>:size</code> MB.",
 *  warning = "The size of the database <code>:db</code> could be better, currently <code>:size</code> MB.",
 *  failure = "The size of the database <code>:db</code> is too large, currently <code>:size</code> MB. The largest tables are - <ol><li><code>:tables</code></li></ol>",
 *  exception = "Could not determine database size.",
 * )
 */
class DatabaseSize extends Check {

  /**
   * {@inheritdoc}
   */
  public function check() {
    $db = $this->context->drush->getCoreStatus('db-name');

    $output = $this->context->drush->sqlQuery("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 'DB Size in MB' FROM information_schema.tables WHERE table_schema='{$db}' GROUP BY table_schema;");
    $output = array_filter($output);
    $size = (float) end($output);

    $max_size = (float) $this->getOption('max_size', 1000);
    $warning_size = (float) $this->getOption('warning_size', 250);
    $this->setToken('max_size', $max_size);
    $this->setToken('warning_size', $warning_size);
    $this->setToken('db', $db);
    $this->setToken('size', number_format($size, 1));

    // Find the top tables by size when the check is above the maximum size, to
    // better inform the developers on how to fix.
    if ($size >= $max_size) {
      $rows = $this->context->drush->sqlQuery("SELECT table_name AS 'Table', ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 'Table Size in MB' FROM information_schema.tables WHERE table_schema='{$db}' GROUP BY table_name ORDER BY (data_length + index_length) DESC LIMIT 3;");
      $rows = array_filter($rows);
      $rows = array_map('trim', $rows);

      // Split by tab.
      $tables = [];
      foreach ($rows as $row) {
        if (preg_match('/^Table/', $row)) {
          continue;
        }
        $parts = explode("\t", $row);
        $table = $parts[0];
        $size = number_format((float) $parts[1], 1);
        $tables[] = "{$table} [{$size} MB]";
      }

      $this->setToken('tables', implode('</code></li><li><code>', $tables));

      return AuditResponse::AUDIT_FAILURE;
    }
    if ($size >= $warning_size) {
      return AuditResponse::AUDIT_WARNING;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }

}
