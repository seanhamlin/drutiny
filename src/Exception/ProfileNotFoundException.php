<?php

namespace Drutiny\Exception;

use Symfony\Component\Console\Exception\ExceptionInterface;

/**
 *
 */
class ProfileNotFoundException extends \Exception implements ExceptionInterface {

  /**
   *
   */
  public function __construct($message, $code = 0, \Exception $previous = NULL) {
    $message = "Invalid profile given: $message";
    parent::__construct($message, $code, $previous);
  }

}
