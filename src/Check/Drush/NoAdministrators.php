<?php

namespace SiteAudit\Check\Drush;

use SiteAudit\Check\Check;
use SiteAudit\AuditResponse\AuditResponse;

class NoAdministrators extends Check {
  static public function getNamespace()
  {
    return 'variable/no_administrators';
  }

  public function check()
  {
    $admin_role = $this->context->drush->getVariable('user_admin_role');
    if(isset($admin_role)) {
      $user_roles = $this->context->drush->getAllUserRoles();
      $admin_count = 0;
      foreach ($user_roles as $role) {
        if(is_numeric($role) && (int)$admin_role === (int)$role) {
          $admin_count++;
        }
      }
      if($admin_count > 0) {
        $this->setToken('value', $admin_count);
        return AuditResponse::AUDIT_FAILURE;
      }
    } else {
      return AuditResponse::AUDIT_NA;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
