<?php

namespace SiteAudit\Profile;

use Symfony\Component\ClassLoader\ClassMapGenerator;
use Symfony\Component\Yaml\Yaml;
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
   * Application file paths.
   *
   * @var array
   */
  protected $filepaths = array(
    'system' => 'profiles',
    'local' => '.',
    'user' => '~/.site-audit',
    'global' => '/etc/site-audit/profiles',
  );

  /**
   * The real path to the yml profile configuration file.
   *
   * @var bool|string
   */
  protected $filepath = FALSE;

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

  /**
   * Store a configured profile on disk.
   *
   * @return int
   */
  public function save()
  {
    $data['metadata']['title'] = $this->getTitle();
    $data['metadata']['machine_name'] = $this->getMachineName();
    $data['checks'] = $this->checks;

    $yaml = Yaml::dump($data);

    if (!$this->filepath) {
      $this->filepath = $this->filepaths['local'] . '/' . $this->getMachineName() . '.yml';
    }

    return file_put_contents($this->getFilepath(), $yaml);
  }

  /**
   * Load a profile by a given machine name.
   *
   * @param string $machine_name
   *   A string for the machine name.
   *
   * @return bool
   *   If the file was successfully loaded.
   */
  public function load($machine_name = '')
  {
    if (!$this->filepath = $this->find($machine_name)) {
      return FALSE;
    }

    $yaml = file_get_contents($this->getFilepath($machine_name));
    $data = Yaml::parse($yaml);
    $this->setTitle($data['metadata']['title'])
         ->setMachineName($data['metadata']['machine_name']);

    $this->checks = $data['checks'];
    $this->settings = $data['settings'];

    return TRUE;
  }

  /**
   * Find a yml profile file by a machine name.
   *
   * @param string $machine_name
   *   A string for the machine name.
   *
   * @return bool|string
   *   The file path or FALSE.
   */
  public function find($machine_name = '')
  {
    $filename = $machine_name . '.yml';
    foreach (array_filter($this->filepaths, 'is_dir') as $filepath) {
      $location = $filepath . '/' . $filename;
      if (file_exists($location)) {
        return $location;
      }
    }
    return FALSE;
  }

  /**
   * Accessor for filepath.
   *
   * @return bool|string
   *   File path for the yml file or FALSE.
   */
  public function getFilepath()
  {
    return $this->filepath;
  }
}
