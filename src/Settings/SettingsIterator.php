<?php

namespace Drutiny\Settings;

use Drutiny\Base\DrushCaller;

/**
 * @file
 * Contains Drutiny\Settings\SettingsIterator.
 */
/**
 *
 */
abstract class SettingsIterator implements \Iterator {

  /**
   * Position in the array.
   *
   * @var int
   */
  private $position;

  /**
   * Settings keys provided.
   *
   * @var array
   */
  private $keys;

  /**
   * Settings values.
   *
   * @var array
   */
  private $values;

  /**
   * An instance of the DrushCaller.
   *
   * @TODO: Consider injecting or having accessible as a singleton.
   *
   * @var \Drutiny\Base\DrushCaller
   */
  private $drush;

  /**
   * SettingsIterator constructor.
   *
   * @param array $settings
   *   An array of settings {setting key} => {value}.
   * @param \Drutiny\Base\DrushCaller $drush
   *   An instance of DrushCaller so we can validate settings.
   */
  public function __construct(array $settings = [], DrushCaller $drush = NULL) {
    $this->position = 0;
    $this->keys = array_keys($settings);
    $this->values = array_values($settings);
    $this->drush = $drush;
  }

  /**
   * Restart the iterator.
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * Return the current value.
   *
   * @return mixed
   */
  public function current() {
    return $this->values[$this->position];
  }

  /**
   * Return the current key.
   *
   * @return string
   */
  public function key() {
    return $this->keys[$this->position];
  }

  /**
   * Move the iterator through the array.
   *
   * @return bool
   *   If there are more items to iterate through.
   */
  public function next() {
    ++$this->position;
    return $this->position < count($this->keys);
  }

  /**
   * Accessor for the Drush instance.
   *
   * @return \Drutiny\Base\DrushCaller
   */
  public function drush() {
    return $this->drush;
  }

  /**
   *
   */
  abstract public function valid();

}
