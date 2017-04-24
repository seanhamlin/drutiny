<?php

namespace Drutiny\Check\Database;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Database size",
 *  description = "Large databases can negatively impact your production site, and slow down things like database dumps.",
 *  remediation = "Find out what tables are the largest and see what can be done to make them smaller.",
 *  success = "The size of the database <code>:db</code> is good, currently <code>:size</code> MB.",
 *  warning = "The size of the database <code>:db</code> could be better, currently <code>:size</code> MB.",
 *  failure = "The size of the database <code>:db</code> is too large, currently <code>:size</code> MB.",
 *  exception = "Could not determine database size.",
 * )
 */
class DatabaseSize extends Check {

  /**
   * {@inheritdoc}
   */
  public function check(Sandbox $sandbox) {
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
      return AuditResponse::WARNING;
    }

    return TRUE;
  }

}
