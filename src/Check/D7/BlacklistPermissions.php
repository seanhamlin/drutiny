<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "BlackList Permissions",
 *  description = "Checks to ensure roles do not contain blacklisted permissions.",
 *  remediation = "Remove blacklisted permissions from roles.",
 *  success = "No blacklisted permissions in use.",
 *  failure = "The following permissions should not be configured: :roles.",
 *  exception = "Could not determine use of blackisted roles.",
 * )
 */
class BlacklistPermissions extends Check {

  /**
   *
   */
  public function check() {
    $perms = $this->getOption('permissions');
    if (empty($perms)) {
      return TRUE;
    }

    $where = [];
    foreach ($perms as $perm) {
      $where[] = 'rp.permission = \'' . $perm . '\'';
    }

    // We don't care about the 'administrator' role having access.
    $admin_role = $this->context->drush->getVariable('user_admin_role', 0);

    try {
      $output = $this->context->drush->sqlQuery('SELECT r.rid, r.name, rp.permission FROM {role} r INNER JOIN {role_permission} rp ON rp.rid = r.rid WHERE r.rid != ' . $admin_role . ' AND (' . implode(' OR ', $where) . ');');
      $output = array_filter($output);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if (empty($output)) {
      return TRUE;
    }

    $black_roles = [];
    foreach ($output as $line) {
      list($rid, $role, $permission) = explode("\t", $line);
      $black_roles[$role][] = $permission;
    }

    foreach ($black_roles as $role => &$perms) {
      if ($role == 'name' && $perms[0] == 'permission') {
        unset($black_roles[$role]);
        continue;
      }
      $perms = '<br /><strong>' . $role . ':</strong> <code>' . implode('</code>, <code>', $perms) . '</code>';
    }

    $this->setToken('roles', implode("\n", $black_roles));

    return FALSE;
  }

}
