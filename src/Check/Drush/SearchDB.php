<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\Executor\DoesNotApplyException;

class SearchDB extends Check {
  static public function getNamespace()
  {
    return 'variable/search_db';
  }

  public function check() {
    if (!$this->context->drush->moduleEnabled('search')) {
      throw new DoesNotApplyException();
    }

    // Check if the database is used used to search.
    if ($this->context->drush->moduleEnabled('search_api_db')) {
      // If the database is in use, find out how many nodes are in it.
      $result = $this->context->drush->sqlq('"SELECT COUNT(item_id) FROM search_api_db_default_node_index;"');
      $output = $result->getOutput();

      // There are some differences in running the command on site factory then
      // locally.
      if (count($output) == 1) {
        $nodes_in_search = (int) $output[0];
      }
      else {
        $nodes_in_search = (int) $output[1];
      }

      $this->setToken('nodes_in_search', $nodes_in_search);
      return FALSE;
    }

    return TRUE;

  }
}

