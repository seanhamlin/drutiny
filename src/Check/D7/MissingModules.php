<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Missing modules",
 *  description = "The warning was introduced in Drupal 7.50 and is displayed when Drupal is attempting to find a module or theme in the file system, but either cannot find it or does not find it in the expected place.",
 *  remediation = "Run this check with auto remediation enabled.",
 *  success = "No missing modules.:fixups",
 *  failure = "Missing modules found :missing.",
 *  exception = "Could not determine missing modules.",
 *  supports_remediation = TRUE,
 * )
 */
class MissingModules extends Check {

  /**
   * Contains a reference to the array of missing modules on the site.
   * @var array
   */
  private $rows = [];

  /**
   * @inheritDoc
   */
  public function check() {
    $this->rows = $this->context->drush->runScript('MissingModules');

    // These are special modules that have their own patches already.
    // This will help eliminate some of the brute force of this module.
    // @TODO use this.
    // $special = array(
    //   'adminimal_theme' => 'https://www.drupal.org/node/2763581',
    //   'content' => 'https://www.drupal.org/node/2763555',
    //   'field_collection_table' => 'https://www.drupal.org/node/2764331',
    // );

    $missing_modules = [];
    foreach ($this->rows as $row) {
      $missing_modules[] = $row->name;
    }
    $this->setToken('missing', '<code>' . implode('</code>, <code>', $missing_modules) . '</code>');

    return count($this->rows) === 0;
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    foreach ($this->rows as $row) {
      $output = $this->context->drush->sqlQuery("DELETE FROM system WHERE name = '{$row->name}' AND type = '{$row->type}';");
    }
    return TRUE;
  }

}
