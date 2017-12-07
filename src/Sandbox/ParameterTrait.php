<?php

namespace Drutiny\Sandbox;

/**
 *
 */
trait ParameterTrait {

  /**
   * @var array
   */
  protected $params = [];

  /**
   * Expose parameters to the check.
   */
  public function getParameter($key, $default_value = NULL) {
    if (isset($this->params[$key])) {
      return $this->params[$key];
    }

    $defaults = $this->sandbox()
      ->getPolicy()
      ->getParameterDefaults();

    if (isset($defaults[$key])) {
      $default_value = $defaults[$key];
    }

    // Ensure default values are recorded for use as tokens.
    $this->setParameter($key, $default_value);
    return $default_value;
  }

  /**
   *
   */
  public function hasParameter($key) {
    return isset($this->params[$key]);
  }

  /**
   *
   */
  public function setParameter($key, $value) {
    $this->params[$key] = $value;
    return $this;
  }

  /**
   *
   */
  public function setParameters(array $params) {
    $this->params = $params;
    return $this;
  }

  /**
   *
   */
  public function getParameterTokens() {
    $tokens = [];
    foreach ($this->params as $key => $value) {
      $tokens[$key] = $value;
    }
    return $tokens;
  }

}
