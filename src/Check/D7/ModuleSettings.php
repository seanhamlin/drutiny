<?php
/**
 * @file
 * Contains SiteAudit\Check\D7\LoginSecurity
 */

namespace SiteAudit\Check\D7;


use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Check\Check;
use SiteAudit\Annotation\CheckInfo;

use Symfony\Component\Debug\Exception\ClassNotFoundException;

/**
 * @CheckInfo(
 *   title = "Module Settings",
 *   description = "Ensure :module is configured correctly",
 *   remediation = "Ensure that the :module has the following settings <ul><li>:settings</li></ul>",
 *   success = ":module is and correctly configured.",
 *   failure = "Found <code>:error_count</code> error:plural with :module",
 *   exception = "Error finding Login Security",
 *   not_available = "Cannot find configuration for Login Security.",
 * )
 */
class ModuleSettings extends Check {

  public function __construct(\SiteAudit\Context $context, array $options) {
    parent::__construct($context, $options);
  }

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
    $label = $this->getOption('label', $machine_name);

    if (empty($label)) {
      throw new \Exception();
    }

    return $label;
  }

  /**
   * Return a list of \SiteAudit\Settings\Settings iterators.
   *
   * Construct all settings objects and prepare them for checks.
   *
   * @return array
   *   Array of \SiteAudit\Settings\Settings.
   *
   * @throws \Symfony\Component\Debug\Exception\ClassNotFoundException
   */
  private function getSettings() {
    $settings = [];

    foreach ($this->getOption('settings', []) as $iterator => $values) {
      if (!class_exists($iterator)) {
        throw new ClassNotFoundException($iterator);
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
    $this->setToken('module', $this->getLabel());
    $errors = [];

    foreach ($this->getSettings() as $setting) {
      do {
        if (!$setting->valid()) {
          $errors[] = "{$setting->key()} should be <b>{$setting->current()}</b>";
        }
      } while($setting->next());
    }

    $this->setToken('settings', implode('</li><li>', $errors));
    $this->setToken('error_count', count($errors));
    $this->setToken('plural', count($errors) > 1 ? 's' : '');

    return empty($errors) ? AuditResponse::AUDIT_SUCCESS : AuditResponse::AUDIT_FAILURE;
  }
}
