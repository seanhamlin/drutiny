<?php

namespace Drutiny\Sandbox;

use Psr\Log\LoggerInterface;

/**
 *
 */
trait LoggerTrait {
  protected $logger;

  /**
   *
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   *
   */
  public function logger() {
    return $this->logger;
  }

}
