<?php

namespace SiteAudit\Base;

use SiteAudit\Executor\ExecutorInterface;

class DrushCaller {
  protected $executor;

  protected $alias;
  protected $modulesList = NULL;
  protected $variablesList = NULL;
  protected $args = [];

  public function __construct(ExecutorInterface $executor) {
    $this->executor = $executor;
  }

  public function setAlias($alias) {
    $this->alias = $alias;
    return $this;
  }

  public function setArgument($arg) {
    $this->args[] = $arg;
    return $this;
  }

  public function __call($method, $args) {
    // Convert method from camelCase to Drush hyphen based method naming.
    // E.g. PmInfo will become pm-info.
    preg_match_all('/((?:^|[A-Z])[a-z]+)/',$method,$matches);
    $method = implode('-', array_map('strtolower', $matches[0]));

    $command = ['drush'];
    if (!empty($this->alias)) {
      $command[] = '@' . $this->alias;
    }

    foreach ($this->args as $arg) {
      $command[] = $arg;
    }

    foreach ($args as &$arg) {
      $arg = '"' . addcslashes($arg, '"') . '"';
    }

    $command[] = $method;
    $command = array_merge($command, $args);
    return $this->executor->execute(implode(' ', $command));
  }

  /**
   * Try to find out if a module is enabled or not.
   *
   * @param $name
   * @return bool
   *   TRUE if the module is enabled, FALSE otherwise (including if the module
   *   was not found).
   */
  public function moduleEnabled($name) {
    try {
      // First time this is run, refresh the module list.
      if (is_null($this->modulesList)) {
        $this->modulesList = $this->pmList('--format=json')->parseJson();
      }

      if (isset($this->modulesList->{$name})) {
        $status = $this->modulesList->{$name}->status;
        return ($status === "Enabled");
      }

      // Module is not in the codebase.
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Try to return a variable value.
   *
   * @param $name
   *   The name of the variable (exact).
   * @param int $default
   *   The value to return if the variable is not set.
   * @return mixed
   */
  public function getVariable($name, $default = 0) {
    try {
      // First time this is run, refresh the variable list.
      if (is_null($this->variablesList)) {
        $this->variablesList = $this->variableGet('--format=json')->parseJson();
      }

      if (isset($this->variablesList->{$name})) {
        return $this->variablesList->{$name};
      }

      return $default;
    }
    // The response from Drush can be "No matching variable found.", even with
    // JSON being requested, which is weird.
    catch (\Exception $e) {
      return $default;
    }
  }

  public function getAllUserRoles() {
    $result = $this->sqlq("'SELECT * from users_roles where uid > 1'");
    return $result->getOutput();
  }

  public function getAllRoles() {
    return $this->roleList('--format=json')->parseJson(TRUE);
  }

  /**
   * Try to return a list of roles assigned to a permission
   *
   * @param $permission
   *  The permission name,e.g. 'administer nodes'
   *
   * @return list
   *  Return a list of roles assigned to the permission
   */
  public function getRolesForPermission($permission) {
    try {
      return $this->roleList('--format=json --filter=\''.$permission.'\'')->parseJson(TRUE);
    } catch (\Exception $e) {
      return NULL;
    }
  }
}
