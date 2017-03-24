<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Missing modules",
 *  description = "The warning was introduced in Drupal 7.50 and is displayed when Drupal is attempting to find a module or theme in the file system, but either cannot find it or does not find it in the expected place.",
 *  remediation = "Either put the modules back in the codebase, or alternatively run this check with auto-remediation enabled to manually remove them from the database. <strong>Warning</strong> - many modules do important cleanup tasks during the uninstall process, and this solution will result in that being skipped. If at all possible, restore the missing module and uninstall it using the regular uninstall process.",
 *  success = "No missing modules.:fixups:special",
 *  failure = "Missing modules found :missing.:special",
 *  exception = "Could not determine missing modules.",
 *  supports_remediation = TRUE,
 * )
 */
class MissingModules extends Check {

  /**
   * Contains a reference to the array of missing modules on the site.
   *
   * @var array
   */
  private $rows = [];

  /**
   * These are special modules that have their own patches already. This will
   * help eliminate some of the brute force of this module.
   *
   * @var array
   */
  private $special = [
    'adminimal_theme' => 'https://www.drupal.org/node/2763581',
    'content' => 'https://www.drupal.org/node/2763555',
    'field_collection_table' => 'https://www.drupal.org/node/2764331',
  ];

  /**
   * @inheritDoc
   */
  public function check() {
    $this->rows = (array) $this->context->drush->runScript('MissingModules');

    $special_modules = [];
    $this->setToken('special', '');
    foreach ($this->rows as $row) {
      if (array_key_exists($row->name, $this->special)) {
        unset($this->rows[$row->name]);
        $this->setToken('special', $this->getOption('special', '') . " The <code>{$row->name}</code> module has a patch, see <a href='{$this->special[$row->name]}'>this issue</a> for more information.");
      }
    }

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
