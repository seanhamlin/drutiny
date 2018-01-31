<?php

namespace Drutiny\Driver;

class DrushFormatException extends \Exception {
  protected $output;

  public function __construct($message, $output, $code = 0, $throwable = null) {
    $this->output = $output;
    parent::__construct($message, $code, $throwable);
  }

  public function getOutput() {
    return $this->output;
  }
}
