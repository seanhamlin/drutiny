<?php

namespace Drutiny\Executor;

/**
 *
 */
class ResultException extends \Exception {
  protected $result;

  /**
   *
   */
  public function __construct($message, Result $result) {
    parent::__construct($message);
    $this->result = $result;
  }

}
