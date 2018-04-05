<?php

namespace Drutiny\Audit\Database;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Queries involving tables with FULLTEXT indexing could lead to performance problems.
 * @Token(
 *  name = "tables",
 *  type = "array",
 *  description = "Tables with fulltext indexes."
 * )
 */
class DatabaseFulltext extends Audit {

  /**
   * {@inheritdoc}
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])
      ->status();

    $dbName = $stat['db-name'];

    $tablesWithFulltextIndexes = $this->getFulltextTableNames($dbName, $sandbox);

    $sandbox->setParameter('tables', $tablesWithFulltextIndexes);

    if (!$tablesWithFulltextIndexes) {
      return Audit::SUCCESS;
    }

    return Audit::FAIL;
  }

  public function getFulltextTableNames($dbName, Sandbox $sandbox) {
    $sql = "SELECT table_name
            FROM information_schema.statistics
            WHERE index_type = 'FULLTEXT'
            AND table_schema = '{$dbName}';";

    return $sandbox->drush()->sqlq($sql);
  }

}
