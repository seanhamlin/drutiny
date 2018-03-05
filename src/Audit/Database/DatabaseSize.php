<?php

namespace Drutiny\Audit\Database;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;

/**
 *  Large databases can negatively impact your production site, and slow down things like database dumps.
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
