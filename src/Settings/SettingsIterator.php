<?php

namespace SiteAudit\Settings;


/**
 * @file
 * Contains SiteAudit\Settings\SettingsIterator
 */
abstract class SettingsIterator implements \Iterator {

  private $position;

  private $keys;

  private $values;

  private $drush;

  public function __construct(array $settings = [], $drush = NULL) {
    $this->position = 0;
    $this->keys = array_keys($settings);
    $this->values = array_values($settings);
    $this->drush = $drush;
  }

  function rewind() {
    $this->position = 0;
  }

  function current() {
    return $this->values[$this->position];
  }

  function key() {
    return $this->keys[$this->position];
  }

  function next() {
    ++$this->position;
    return $this->position == count($this->keys);
  }

  function drush() {
    return $this->drush;
  }

  abstract function valid();

}
