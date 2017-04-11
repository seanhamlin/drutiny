<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "No administrators",
 *  description = "There should be no administrators other than user ID #1.",
 *  remediation = "Check that no users have the 'administrator' role, other than user ID #1.",
 *  success = "No administrators found, other than user ID #1.",
 *  failure = "Currently there are <code>:count</code> administrator:plural - <ul><li><code>:issues</code></li></ul>",
 *  not_available = "There is no administrator role defined.",
 *  supports_remediation = TRUE,
 * )
 */
class NoAdministrators extends Check {

  /**
   * Cache the administrator role rids.
   * @var array
   */
  private $adminRolesRids = [];

  /**
   * Cache the administrator role names.
   * @var array
   */
  private $adminRolesNames = ['administrator'];

  /**
   * Cache of administrators.
   * @var array
   */
  private $adminUids = [];

  /**
   * @inheritDoc
   */
  public function check() {
    $this->adminRolesRids[] = (int) $this->context->drush->getVariable('user_admin_role', 0);
    $role_list = $this->context->drush->getAllRoles();

    foreach ($role_list as $role) {
      // We need to check if there is a role called 'administrator', as this could
      // be different from 'user_admin_role'.
      if ($role['label'] === 'administrator') {
        $this->adminRolesRids[] = (int) $role['rid'];
      }
      // Find out the role name of the role ID defined in 'user_admin_role'.
      if ($role['rid'] === $this->adminRolesRids[0]) {
        $this->adminRolesNames[] = $role['label'];
      }
    }

    // Remove duplicates.
    $this->adminRolesRids = array_unique($this->adminRolesRids);
    $this->adminRolesNames = array_unique($this->adminRolesNames);

    // No administrator roles defined, and no roles called 'administrator' in
    // Drupal.
    if (empty($this->adminRolesRids)) {
      return AuditResponse::AUDIT_NA;
    }

    // '0' is disabled.
    // @see https://github.com/drupal/drupal/blob/7.x/modules/user/user.admin.inc#L305
    if (count($this->adminRolesRids) === 1 && $this->adminRolesRids[0] === 0) {
      return AuditResponse::AUDIT_NA;
    }

    if (count($this->adminRolesRids) === 1) {
      $rows = $this->context->drush->sqlQuery("SELECT ur.uid, u.name FROM {users_roles} ur LEFT JOIN {users} u ON ur.uid = u.uid WHERE ur.uid > 1 AND ur.rid = {$this->adminRolesRids[0]};");
    }
    else {
      $rows = $this->context->drush->sqlQuery("SELECT ur.uid, u.name FROM {users_roles} ur LEFT JOIN {users} u ON ur.uid = u.uid WHERE ur.uid > 1 AND ur.rid IN (" . implode(',', $this->adminRolesRids) . ");");
    }

    // Remove blank rows.
    $rows = array_filter($rows);
    if (empty($rows)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    $rows = array_map('trim', $rows);

    // Split by tab.
    $administrators = [];
    foreach ($rows as $row) {
      $parts = explode("\t", $row);
      $uid = (int) $parts[0];
      $name = trim($parts[1]);
      $administrators[] = "{$name} - [UID {$uid}]";
      // Needed for remediation.
      $this->adminUids[] = $uid;
    }

    $this->setToken('count', count($administrators));
    $this->setToken('issues', implode('</code></li><li><code>', $administrators));
    $this->setToken('plural', count($administrators) > 1 ? 's' : '');

    return AuditResponse::AUDIT_FAILURE;
  }

  /**
   * @inheritDoc
   *
   * @see https://drushcommands.com/drush-8x/user/user-remove-role/
   */
  public function remediate() {
    $success = TRUE;
    foreach ($this->adminRolesNames as $role_name) {
      $res = $this->context->drush->userRemoveRole("'{$role_name}'", '--uid=' . implode(',', $this->adminUids));
      $success = $success && $res->isSuccessful();
    }
    return $success;
  }

}
