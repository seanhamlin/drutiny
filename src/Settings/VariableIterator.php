<?php
/**
 * @file
 * Contains SiteAudit\Settings\VariableIterator
 */

namespace SiteAudit\Settings;


class VariableIterator extends SettingsIterator {

  /**
   * Determine if a variable is configured correctly.
   *
   * @return bool
   */
  public function valid() {
    $actual = $this->drush()->getVariable($this->key());
    return $this->current() == $actual;
  }

}
