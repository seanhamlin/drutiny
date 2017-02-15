<?php
/**
 * @file
 * Contains Drutiny\Check\D7\LoginSecurity
 */

namespace Drutiny\Settings;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *   title = "Module (:module) settings",
 *   description = "Ensure :module is configured correctly",
 *   remediation = "Ensure that the :module has the following settings <ul><li>:settings</li></ul>",
 *   success = ":module is correctly configured.",
 *   failure = "Found <code>:error_count</code> error:plural with :module configuration",
 *   exception = "Error finding :module.",
 *   not_available = "Cannot find configuration for :module.",
 * )
 */
class SettingsCheck extends Check {

  /**
   * Get the modules label.
   *
   * @return string
   *   The module name.
   *
   * @throws \Exception
   */
  private function getLabel() {
    $machine_name = $this->getOption('machine_name', FALSE);
    return $this->getOption('label', $machine_name);
  }

  /**
   * Return a list of \Drutiny\Settings\Settings iterators.
   *
   * Construct all settings objects and prepare them for checks.
   *
   * @return array
   *   Array of \Drutiny\Settings\Settings.
   *
   * @throws \Exception
   */
  private function getSettings() {
    $settings = [];

    foreach ($this->getOption('settings', []) as $iterator => $values) {
      if (!class_exists($iterator)) {
        throw new \Exception("Unable to load iterator: $iterator");
      }
      $settings[$iterator] = new $iterator($values, $this->context->drush);
    }

    return $settings;
  }

  /**
   * Run a check to ensure a module is configured correctly.
   *
   * @return int
   *   Response code from AuditResponse.
   */
  protected function check() {
    $errors = [];

    foreach ($this->getSettings() as $setting) {
      do {
        if (!$setting->valid()) {
          $errors[] = "<code>{$setting->key()}</code> should be <b>{$setting->current()}</b>";
        }
      } while($setting->next());
    }

    $this->setToken('module', $this->getLabel());
    $this->setToken('settings', implode('</li><li>', $errors));
    $this->setToken('error_count', count($errors));
    $this->setToken('plural', count($errors) > 1 ? 's' : '');

    return empty($errors) ? AuditResponse::AUDIT_SUCCESS : AuditResponse::AUDIT_FAILURE;
  }
}
