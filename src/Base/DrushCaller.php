<?php

namespace SiteAudit\Base;

use SiteAudit\Executor\ExecutorInterface;

class DrushCaller {
  protected $executor;
  protected $drushAlias;

  protected $alias;
  protected $modulesList = NULL;
  protected $variablesList = NULL;
  protected $configurationList = NULL;
  protected $drushStatus = NULL;
  protected $db_prefix = NULL;
  protected $args = [];
  protected $isRemote = FALSE;
  protected $singleSite = TRUE;

  public function __construct(ExecutorInterface $executor, $drushAlias = 'drush') {
    $this->executor = $executor;

    // @todo validate this alias.
    $this->drushAlias = $drushAlias;
    $this->configurationList = new \stdClass();
  }

  public function setAlias($alias) {
    $this->alias = $alias;
    return $this;
  }

  public function setArgument($arg) {
    $this->args[] = $arg;
    return $this;
  }

  public function setIsRemote($isRemote) {
    $this->isRemote = $isRemote;
    return $this;
  }

  public function setSingleSite($singleSite) {
    $this->singleSite = $singleSite;
    return $this;
  }

  public function __call($method, $args) {
    // Convert method from camelCase to Drush hyphen based method naming.
    // E.g. PmInfo will become pm-info.
    preg_match_all('/((?:^|[A-Z])[a-z]+)/',$method,$matches);
    $method = implode('-', array_map('strtolower', $matches[0]));

    $command = [$this->drushAlias];
    if (!empty($this->alias)) {
      $command[] = '@' . $this->alias;
    }

    foreach ($this->args as $arg) {
      $command[] = $arg;
    }

    foreach ($args as &$arg) {
      // no need to quote drush arguments.
      if (strpos($arg, '--') === 0) {
        $arg = addcslashes($arg, '"');
      }
      /*else {
        $arg = "'" . addcslashes($arg, '"') . "'";
      }*/
    }

    $command[] = $method;
    $command = array_merge($command, $args);
    return $this->executor->execute(implode(' ', $command));
  }

  /**
   * Execute a PHP script in the context of the Drupal site. This uses a rather
   * tricky amount of base64 encoding to ensure no characters are lost.
   *
   * @param [String] $filename
   *   The filename of the script in the './src/Scripts/' folder. The .php
   *   extens is not required. The script in question should return JSON for
   *   all of it's results.
   * @return [String]
   *   The result of the Drush command.
   */
  public function runScript($filename) {
    $location = './src/Scripts/' . $filename . '.php';
    if (!file_exists($location)) {
      throw new \Exception("No script found at $location.");
    }
    $base64 = base64_encode(file_get_contents($location));

    // Remote execution on multiple sites requires more escaping.
    if ($this->isRemote && !$this->singleSite) {
      $output = $this->phpEval('\"' . "eval(base64_decode('" . $base64 . "'));" . '\"')->parseJson();
    }
    else {
      $output = $this->phpEval('"' . "eval(base64_decode('" . $base64 . "'));" . '"')->parseJson();
    }

    return $output;
  }

  /**
   * Wrapper function to hide the quoting madness.
   */
  public function sqlQuery($sql) {
    global $argv;

    // Database prefixes need to be added else the query will fail when this is
    // in use.
    if (is_null($this->db_prefix)) {
      $sql_conf = $this->sqlConf('--format=json')->parseJson();

      // You can have multiple different prefixes.
      if (is_object($sql_conf->prefix)) {
        $this->db_prefix = $sql_conf->prefix->default;
      }
      else {
        $this->db_prefix = $sql_conf->prefix;
      }
    }

    // Replace the curly braces.
    $sql = str_replace(array('{', '}'), array($this->db_prefix, ''), $sql);

    // Remote execution on multiple sites requires more escaping.
    if ($this->isRemote && !$this->singleSite) {
      $result = $this->sqlq('\"' . $sql . '\"');
    }
    else {
      $result = $this->sqlq('"' . $sql . '"');
    }

    return $result->getOutput();
  }


  /**
   * Get a drush core status from the site.
   *
   * @param $key [string]
   * @return a drush core status object.
   */
  public function getCoreStatus($key = NULL) {
    try {
      // First time this is run, refresh the drush core statuss list.
      if (is_null($this->drushStatus)) {
        $this->drushStatus = $this->coreStatus('--format=json')->parseJson();
      }

      // Shortcut if you provide a key.
      if ($key && isset($this->drushStatus->{$key})) {
        return $this->drushStatus->{$key};
      }

      return $this->drushStatus;
    }
    catch (\Exception $e) {
      return FALSE;
    }
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

  /**
   * Try to return a configuration value.
   *
   * @param $configName
   *   The config object name, for example "system.site".
   * @param $key
   *   The config key, for example "page.front".
   * @param int $default
   *   The value to return if the configuration key is not set.
   * @return mixed
   */
  public function getConfig($configName, $key, $default = 0) {
    try {
      // First time this is run, refresh the variable list.
      if (!isset($this->configurationList->{$configName})) {
        $this->configurationList->{$configName} = $this->configGet($configName, '--format=json')->parseJson();
      }

      $base_config = $this->configurationList->{$configName};

      // Explode the key, the JSON can be nested, we need to extract the right
      // part of the JSON.
      $key_parts = explode('.', $key);
      foreach ($key_parts as $key_part) {
        $base_config = $base_config->{$key_part};
      }

      // @todo return default value when the $key is missing.
      return $base_config;
    }
    // The response from Drush can be "No matching variable found.", even with
    // JSON being requested, which is weird.
    catch (\Exception $e) {
      return $default;
    }
  }

  /**
   * Determine if shield module is active, and is also enabled.
   *
   * @return boolean
   */
  public function isShieldEnabled() {
    // If the module is disabled, then no shield.
    if ($this->moduleEnabled('shield')) {
      // Shield must be enabled, defaults to on.
      $shield_enabled = (bool) (int) $this->getVariable('shield_enabled', 1);
      if ($shield_enabled) {
        // Shield user must be set.
        $shield_user = $this->getVariable('shield_user', '');
        if (!empty($shield_user)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Try to return a variable value, bypass the $conf overrides. Useful for when
   * drush gets in your way.
   *
   * @param $name
   *   The name of the variable (exact).
   * @param int $default
   *   The value to return if the variable is not set.
   * @return mixed
   */
  public function getVariableFromDB($name, $default = 0) {
    try {
      $output = $this->sqlQuery("SELECT value FROM {variable} WHERE name = '$name';");
      if (empty($output)) {
        $value = $default;
      }
      else if (count($output) == 1) {
        if (empty($output[0])) {
          $value = $default;
        }
        else {
          $value = $output[0];
        }
      }
      else {
        $value = $output[1];
      }

      return $value;
    }
    // The response from Drush can be "No matching variable found.", even with
    // JSON being requested, which is weird.
    catch (\Exception $e) {
      return $default;
    }
  }

  public function getAllUserRoles() {
    return $this->sqlQuery('SELECT rid FROM {users_roles} WHERE uid > 1;');
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
      return $this->roleList('--format=json', '--filter="' . $permission . '"')->parseJson(TRUE);
    } catch (\Exception $e) {
      return NULL;
    }
  }
}
