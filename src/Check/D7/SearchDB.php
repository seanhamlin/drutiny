<?php

namespace Drutiny\Check\D7;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Check\Check;
use Drutiny\Executor\DoesNotApplyException;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Search database",
 *  description = "Search backed with the database (and not Solr) can cause performance impacts to your site. Often the SQL queries caused but using the database are slow.",
 *  remediation = "Disable <code>search_api_db</code> and then configure search to use Solr.",
 *  not_available = "Search is not enabled.",
 *  success = "Search is not using the database.",
 *  warning = "Search is using the database. Currently <code>:nodes_in_search</code> nodes in <code>:number_of_db_indexes</code> database index:plural_index.",
 *  failure = "Search is using the database. Currently <code>:nodes_in_search</code> nodes in <code>:number_of_db_indexes</code> database index:plural_index.",
 *  exception = "Could not determine Search settings.",
 * )
 */
class SearchDB extends Check {

  /**
   *
   */
  public function check() {
    if (!$this->context->drush->moduleEnabled('search')) {
      throw new DoesNotApplyException();
    }

    // Check if the database is used used to search.
    if ($this->context->drush->moduleEnabled('search_api_db')) {

      // Find out if there are active indexes using the db service class.
      $output = $this->context->drush->sqlQuery("SELECT COUNT(i.machine_name) as count FROM {search_api_index} i LEFT JOIN {search_api_server} s ON i.server = s.machine_name WHERE i.status > 0 AND s.class = 'search_api_db_service';");
      if (empty($output)) {
        $number_of_db_indexes = 0;
      }
      elseif (count($output) == 1) {
        $number_of_db_indexes = (int) $output[0];
      }
      else {
        $number_of_db_indexes = (int) $output[1];
      }
      $this->setToken('number_of_db_indexes', $number_of_db_indexes);
      $number_of_db_indexes > 1 ? $this->setToken('plural_index', 'es') : $this->setToken('plural_index', '');

      // No database indexes.
      if ($number_of_db_indexes === 0) {
        return AuditResponse::AUDIT_SUCCESS;
      }

      // If the database is in use, find out how many nodes are in it.
      $output = $this->context->drush->sqlQuery('SELECT COUNT(item_id) FROM {search_api_db_default_node_index};');
      // There are some differences in running the command on site factory then
      // locally.
      if (count($output) == 1) {
        $nodes_in_search = (int) $output[0];
      }
      else {
        $nodes_in_search = (int) $output[1];
      }
      $this->setToken('nodes_in_search', $nodes_in_search);

      $max_size = (int) $this->getOption('max_size', 50);
      if ($nodes_in_search < $max_size) {
        return AuditResponse::AUDIT_WARNING;
      }
      return AuditResponse::AUDIT_FAILURE;
    }

    return TRUE;

  }

}
