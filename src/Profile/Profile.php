<?php

namespace SiteAudit\Profile;
use SiteAudit\Check\Registry;

/**
 * Class Profile
 * @package SiteAudit\Profile
 */
class Profile
{

  /**
   * A title for a given profile.
   *
   * @var string
   */
  protected $title;

  /**
   * A machine name for a given profile.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * Checks to run against a Drupal site.
   *
   * @var array
   */
  protected $checks = array();

  /**
   * A list of modules and their settings to ensure correct configuration.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * Profile constructor.
   *
   * @param string $title
   *   A valid title for this profile.
   * @param string $machine_name
   *   A valid machine name for this profile.
   * @param array $checks
   *   Defined checks for this profile.
   * @param array $settings
   *   Defined settings checks for this profile.
   */
  public function __construct($title = '', $machine_name = '', $checks = [], $settings = []) {
    $this->title = $title;
    $this->machine_name = $machine_name;
    $this->checks = $checks;
    $this->settings = $settings;
  }

  /**
   * Set the Profile title
   *
   * @param string $title
   *    A title to set for the profile.
   *
   * @return $this
   *   The profile instance.
   */
  public function setTitle($title)
  {
    $this->title = $title;
    return $this;
  }

  /**
   * Get the Profile title
   *
   * @return string
   *   The profile's title.
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set the Profile machine name
   *
   * @param string $machine_name
   *   A machine name to set for this profile.
   *
   * @return $this
   *   The profile instance.
   */
  public function setMachineName($machine_name = '')
  {
    $this->machine_name = $machine_name;
    return $this;
  }

  /**
   * Get the Profile machine name
   *
   * @return string
   *   The machine name for the profile.
   */
  public function getMachineName()
  {
    return $this->machine_name;
  }

  /**
   * Add a check to the profile.
   *
   * @return bool
   *   If the check was successfully added.
   */
  public function addCheck($check, $options = array())
  {
    $registry = Registry::load();
    file_put_contents(json_encode($registry), '/Users/steven.worley/repos/site-audit/file.txt');
    $reverse_lookup = array_flip($registry);

    // Look for the check in the register as the class name.
    if (isset($registry[$check])) {
      $this->checks[$check] = $options;
    }
    // Look for the check in the register as the namespace.
    elseif (isset($reverse_lookup[$check])) {
      $class = $reverse_lookup[$check];
      $this->checks[$class] = $options;
    }
    else {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the Profile checks.
   *
   * @return array
   *   A list of checks to perform for this profile.
   */
  public function getChecks()
  {
    return $this->checks;
  }

  /**
   * Get the Profile moduel settings.
   *
   * @return array
   *   A list of module settings to verify.
   */
  public function getSettings()
  {
    return $this->settings;
  }
}
