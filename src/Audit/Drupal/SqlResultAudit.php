<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractComparison;
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
 * @Param(
 *  name = "field",
 *  description = "The name of the field in the result row to pull the value from",
 *  type = "string"
 * )
 * @Param(
 *  name = "value",
 *  description = "The value to compare against",
 *  type = "mixed"
 * )
 * @Param(
 *  name = "comp_type",
 *  description = "The comparison operator to use for the comparison.",
 *  type = "string"
 * )
 * @Token(
 *  name = "result",
 *  description = "The comparison operator to use for the comparison.",
 *  type = "string"
 * )
 */
class SqlResultAudit extends AbstractComparison {

  /**
   *
   */
  public function audit(Sandbox $sandbox)
  {
    $query = $sandbox->getParameter('query');

    $tokens = [];
    foreach ($sandbox->getParameterTokens() as $key => $value) {
        $tokens[':' . $key] = $value;
    }
    $query = strtr($query . ' \G', $tokens);

    if (!preg_match_all('/^SELECT (.*) FROM/', $query, $fields)) {
      throw new \Exception("Could not parse fields from SQL query: $query.");
    }

    $output = $sandbox->drush()->sqlq($query);
    $results = [];

    while ($line = array_shift($output))
    {
      if (preg_match('/^[\*]+ ([0-9]+)\. row [\*]+$/', $line, $matches)) {
        $idx = $matches[1];
      }
      else {
        list($field, $value) = explode(':', trim($line), 2);
        $results[$idx][$field] = $value;
      }
    }

    if (empty($results)) {
      return FALSE;
    }
    $row = array_shift($results);

    $field = $sandbox->getParameter('field');
    if (!isset($row[$field])) {
      throw new \Exception("Did not find $field in SQL query: $query.");
    }

     $sandbox->setParameter('result', $row);

    return $this->compare($sandbox->getParameter('value'), $row[$field], $sandbox);
  }

}
