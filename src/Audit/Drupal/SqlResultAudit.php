<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Audit the first row returned from a SQL query.
 * @Param(
 *  name = "query",
 *  description = "The SQL query to run. Can use other parameters for variable replacement.",
 *  type = "string"
 * )
 * @param(
 *  name = "db_level_query",
 *  description = "Whether query is normal sql query like 'SELECT * FROM *' or a DB level query like 'SHOW TABLE STATUS'",
 *  type = "boolean",
 *  default = false
 * )
 * @Param(
 *  name = "expression",
 *  description = "An expression language expression to evaluate a successful auditable outcome.",
 *  type = "string",
 *  default = true
 * )
 * @Token(
 *  name = "result",
 *  description = "The comparison operator to use for the comparison.",
 *  type = "string"
 * )
 * @Token(
 *  name = "results",
 *  description = "The record set.",
 *  type = "string"
 * )
 */
class SqlResultAudit extends AbstractAnalysis {

  /**
   *
   */
  public function gather(Sandbox $sandbox)
  {
    $query = $sandbox->getParameter('query');
    $db_level_query = $sandbox->getParameter('db_level_query');

    $tokens = [];
    foreach ($sandbox->getParameterTokens() as $key => $value) {
        $tokens[':' . $key] = $value;
    }
    foreach ($sandbox->drush(['format' => 'json'])->status() as $key => $value) {
      if (!is_array($value)) {
        $tokens[':' . $key] = $value;
      }
      // TODO: Support array values.
    }
    $query = strtr($query, $tokens);

    if (!$db_level_query) {
      if (!preg_match_all('/^SELECT( DISTINCT)? (.*) FROM/', $query, $fields)) {
        throw new \Exception("Could not parse fields from SQL query: $query.");
      }
      $fields = array_map('trim', explode(',', $fields[2][0]));
      foreach ($fields as &$field) {
        if ($idx = strpos($field, ' as ')) {
          $field = substr($field, $idx + 4);
        }
        elseif (preg_match('/[ \(\)]/', $field)) {
          throw new \Exception("SQL query contains an non-table field without an alias: '$field.'");
        }
      }
    }

    $output = $sandbox->drush()->sqlq($query);
    $results = [];

    while ($line = array_shift($output))
    {
      $values = array_map('trim', explode("\t", $line));
      $results[] = array_combine($fields, $values);
      $results[] = !$db_level_query ? array_combine($fields, $values) : $values;
    }

    $sandbox->setParameter('count', count($results));
    $sandbox->setParameter('results', $results);

    $row = array_shift($results);

    $sandbox->setParameter('first_row', $row);
  }

}
