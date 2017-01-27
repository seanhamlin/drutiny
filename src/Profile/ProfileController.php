<?php
/**
 * @file
 * Contains SiteAudit\Profile\ProfileContorller
 */

namespace SiteAudit\Profile;

use SiteAudit\Exception\ProfileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ProfileController
 *
 * @package SiteAudit\Profile
 */
class ProfileController {

  const FILE_PATHS = [
    'system' => 'profiles',
    'local' => '.',
    'user' => '~/.site-audit',
    'global' => '/etc/site-audit/profiles',
  ];

  /**
   * Load a profile by a given machine name.
   *
   * @param string $machine_name
   *   A string for the machine name.
   *
   * @return bool
   *   If the file was successfully loaded.
   */
  public static function load($machine_name) {
    $profile = self::find($machine_name);

    $yaml = file_get_contents($profile);
    $data = Yaml::parse($yaml);

    extract($data['metadata']);
    /** @var string $title */
    /** @var string $machine_name */

    return new Profile($title, $machine_name, $data['checks'], $data['settings']);
  }

  /**
   * Store a configured profile on disk.
   *
   * @return int
   */
  public static function save(Profile $profile, $file_path = FALSE) {
    $data['metadata']['title'] = $profile->getTitle();
    $data['metadata']['machine_name'] = $profile->getMachineName();
    $data['checks'] = $profile->getChecks();

    $yaml = Yaml::dump($data);

    if (!$file_path) {
      $file_path = self::FILE_PATHS['local'] . '/' . $profile->getMachineName() . '.yml';
    }

    $write_status = file_put_contents($file_path, $yaml);
    return $write_status !== FALSE ? $file_path : $write_status;
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
  public static function find($machine_name) {
    $filename = $machine_name . '.yml';
    foreach (array_filter(self::FILE_PATHS, 'is_dir') as $file_path) {
      $location = $file_path . '/' . $filename;
      if (file_exists($location)) {
        return $location;
      }
    }

    throw new ProfileNotFoundException($machine_name);
  }

  /**
   * Return a list of available profiles.
   *
   * @return array
   *   All available profiles.
   */
  public static function available() {
    $available_profiles = [];

    foreach (array_filter(self::FILE_PATHS, 'is_dir') as $file_path) {
      array_merge($available_profiles, glob("$file_path/*.yml"));
    }

    return $available_profiles;
  }
}
