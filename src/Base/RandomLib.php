<?php

namespace Drutiny\Base;

use Drutiny\Executor\ExecutorInterface;
use RandomLib\Factory;

class RandomLib {
  private $factory = NULL;
  private $generator = NULL;

  public function __construct() {
    $this->factory = new Factory;
    $this->generator = $this->factory->getMediumStrengthGenerator();
  }

  /**
   * Generate a random string
   *
   * @param integer $length [optional]
   *   the length of the string
   * @param string $characters [optional]
   *   you can limit the characters in the string if you want by specifying a
   *   whitelist
   * @return string
   *   the random string
   */
  public function generateRandomString($length = 32, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    return $this->generator->generateString($length, $characters);
  }

}
