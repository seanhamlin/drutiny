<?php
/**
 * @file
 * Contains Drutiny\Settings\VariableIterator
 */

namespace Drutiny\Settings;


class VariablesIteratorD7 extends SettingsIterator {

  /**
   * Determine if a variable is configured correctly.
   *
   * @return bool
   */
  public function valid() {
    $actual = $this->drush()->getVariable($this->key());
    // == is used here as settings get translated from the DB to 0 or 1 settings
    // can also be strings or integers so strict checking doesn't work 100%.
    return $this->current() == $actual;
  }

}
