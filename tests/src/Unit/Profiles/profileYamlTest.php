<?php

use Drutiny\Check\Registry;
use Drutiny\Profile\ProfileController;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Profile\ProfileController
 */
class profileYamlTest extends TestCase {

  /**
   * @covers ::load
   * @group profiles
   */
  public function testYamlIsValid() {
    $profiles = glob("profiles/*.yml");

    // Assert some profiles where found.
    $this->assertGreaterThanOrEqual(count($profiles), 10);

    foreach ($profiles as $index => $profile) {
      // Ensure the YAML is syntactically good.
      $data = Yaml::parse(file_get_contents($profile), Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
      $this->assertTrue(TRUE, "YAML file {$profile} is valid.");

      // Ensure the checks classes are real.
      $checks = array_key_exists('checks', $data) ? $data['checks'] : [];
      $settings = array_key_exists('settings', $data) ? $data['settings'] : [];

      foreach ($checks as $class => $check) {
        $registry = Registry::load();

        // Classes should have a leading slash.
        $class = ltrim($class, '\\');

        // Look for the check in the register as the class name.
        if (isset($registry[$class])) {
          $this->assertTrue(TRUE, "Check class {$class} is valid.");
        }
        else {
          $this->assertTrue(FALSE, "Check class {$class} is not valid, found in profile {$profile}.");
        }
      }
    }
  }

}
