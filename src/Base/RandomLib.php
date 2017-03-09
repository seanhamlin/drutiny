<?php

namespace Drutiny\Base;

use Drutiny\Executor\ExecutorInterface;

class RandomLib {

  /**
   * Generate a random string
   *
   * @param integer $length [optional]
   *   the length of the random string.
   * @return string
   *   the random string.
   */
  public static function generateRandomString($length = 32) {

    // Generate a lot of random characters.
    $state = bin2hex(random_bytes($length * 2));

    // Remove non-alphanumeric characters.
    $state = preg_replace("/[^a-zA-Z0-9]/", '', $state);

    // Trim it down.
    return substr($state, 0, $length);
  }

}
