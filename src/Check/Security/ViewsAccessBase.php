<?php
/**
 * @file
 * Contains Drutiny\Check\Security\ViewsAccessBase
 */

namespace Drutiny\Check\Security;


use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Helpers\Text;
use Drutiny\Check\Check;

abstract class ViewsAccessBase extends Check {

  /**
   * Return a list of views to use for the check.
   *
   * Determine how to get all views for this check. This method should return
   * a list of views that are to be checked for correct permissions. It should
   * attempt to return a views object that mimics a Drupal views object.
   *
   * @see views_get_all_views()
   * @see
   *
   * @return array
   *   A list of views to check.
   */
  abstract protected function getViews();

  final public function check() {

    $count = 0;
    $errors = ['no_access' => [], 'weak' => []];

    // Configurable permissions to check for default is to check to see if
    // the view has been updated to use 'permissions'. This can be configured
    // per profile.
    //
    // Should match:
    //   ['permission type' => 'value'|TRUE]
    //
    // Use 'value' to check for a specific level of permission.
    // Use TRUE to ensure that the permission type is configured.
    $permissions = $this->getOption('permissions', ['perm' => TRUE]);

    foreach ($this->getViews() as $view) {
      if (empty($view->display)) {
        // Ensure we have display's to check.
        continue;
      }

      foreach ($view->display as $display_id => $info) {
        $count++;
        $perms = array_keys($permissions);

        $tokens = [
          ':view' => $view->name,
          ':display' => $display_id,
          ':perms' => implode(', ', $perms),
        ];

        // Check to see if this view has access options configured.
        if (empty($info->display_options['access']['type'])) {
          $errors['no_access'][] = Text::translate("<strong>:view</strong> <code>:display</code> has no permissions", $tokens);
          continue;
        }

        // Check to see if this view has permissions that match the allowed
        // list of permissions for this profile.
        if (!in_array($info->display_options['access']['type'], $perms)) {
          $errors['weak'][] = Text::translate('<strong>:view</strong> <code>:display</code> has incorrect permissions expected one of :perms', $tokens);
          continue;
        }

        // Get the expected value for this permission.
        $value = $permissions[$info->display_options['access']['type']];

        // If the value is a boolean (TRUE) we assume that just having this
        // permission is correct so we can skip this check otherwise we ensure
        // that the permissions meet the configured requirement.
        if (is_string($value) && $info->display_options['access']['perm'] != $value) {
          $tokens[':expected'] = $value;
          $tokens[':real'] = $info->display_options['access']['perm'];
          $errors['weak'][] = Text::translate('<strong>:view</strong> <code>:display</code> expected :expected found :real', $tokens);
        }

      }
    }

    $this->setToken('num', $count);
    $this->setToken('total_errors', count($errors['no_access']) + count($errors['weak']));
    $this->setToken('no_access', implode('</li><li>', $errors['no_access']));
    $this->setToken('no_access_num', count($errors['no_access']));
    $this->setToken('no_access_plural', count($errors['no_access']) > 1 ? 's' : '');
    $this->setToken('weak_num', count($errors['weak']));
    $this->setToken('weak_num_plural', count($errors['weak']) > 1 ? 's' : '');
    $this->setToken('weak', implode('</li><li>', $errors['weak']));

    if ($count == 0) {
      // No views after this? Maybe something has gone wrong.
      return AuditResponse::AUDIT_NA;
    }

    if (empty($errors['no_access']) && empty($errors['weak'])) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    return AuditResponse::AUDIT_FAILURE;
  }

}
