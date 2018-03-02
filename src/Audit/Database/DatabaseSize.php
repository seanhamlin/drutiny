<?php

namespace Drutiny\Audit\Database;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 *  Large databases can negatively impact your production site, and slow down things like database dumps.
 * @Param(
 *  name = "max_size",
 *  description = "Fail the audit if the database size is greater than this value",
 *  type = "integer"
 * )
 * @Param(
 *  name = "warning_size",
 *  description = "Issue a warning if the database size is greater than this value",
 *  type = "integer"
 * )
 * @Token(
 *  name = "db",
 *  description = "The name of the database",
 *  type = "string"
 * )
 * @Token(
 *  name = "size",
 *  description = "The size of the database",
 *  type = "integer"
 * )
 */
class DatabaseSize extends Audit {

  /**
   * {@inheritdoc}
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])
      ->status();

    $name = $stat['db-name'];
    $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 'DB Size in MB'
            FROM information_schema.tables
            WHERE table_schema='{$name}'
            GROUP BY table_schema;";

    $size = (float) $sandbox->drush()->sqlq($sql);

    $sandbox->setParameter('db', $name)
            ->setParameter('size', $size);

    if ($sandbox->getParameter('max_size') < $size) {
      return FALSE;
    }

    if ($sandbox->getParameter('warning_size') < $size) {
      return Audit::WARNING;
    }

    return TRUE;
  }

}
