<?php

/**
 * @file
 * Contains Drutiny\Checks\Security\TextFormatCheck
 */

namespace Drutiny\Check\Security\D7;

use Drutiny\Check\Security\TextFormatBase;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *   title = "Text Format",
 *   description = "Ensure that text formats are configured correctly.",
 *   remediation = "The following are considered harmful consider revising permissions:<br/><ul><li>:errors</li></ul>",
 *   success = "Found <code>:num</code> correctly configured text formats",
 *   failure = "Found <code>:num</code> incorrectly configured text formats",
 *   warning = "The following are considered harmful consider revising permissions:<br/><ul><li>:errors</li></ul>",
 *   exception = "Could not find any text formats",
 *   not_available = "No text formats defined.",
 * )
 */
class TextFormat extends TextFormatBase {

  /**
   * Attempt to build the roles that use the text format.
   *
   * @param string $format
   *   A format name.
   *
   * @return array
   *   All roles that have access to the permission.
   */
  protected function getRoles($format = '') {
    $permission = "use text format {$format}";
    $roles = $this->context->drush->getRolesForPermission($permission);
    return empty($roles) ? [] : $roles;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFilters($format = '') {
    $hooks = $this->context->drush->invokeHook('filter_info', 'all', 'filter_info');
    return empty($hooks) ? [] : $hooks;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormats() {
    $sql = "SELECT format, name, cache, status, weight FROM filter_format ff;";
    $formats = [];

    foreach ($this->context->drush->sqlQuery($sql) as $format) {
      $format = explode("\t", $format);
      $format = array_combine(['format', 'name', 'cache', 'status', 'weight'], $format);

      $formats[$format['format']] = (object) $format;
      $formats[$format['format']]->roles = $this->getRoles($format['format']);
      $formats[$format['format']]->filters = $this->getFilters($format['format']);
    }

    return $formats;
  }

}
