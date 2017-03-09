<?php

use Drutiny\Base\RandomLib;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Base\RandomLib
 */
class RandomLibTest extends TestCase
{
  /**
   * @covers ::generateRandomString
   * @group base
   */
  public function testGenerateRandomString()
  {
    $random_lib = new RandomLib();

    // Ensure the generated string is alphanumeric only.
    for ($i = 0 ; $i < 100 ; $i++) {
      $this->assertRegExp('/^[a-zA-Z0-9]{32}$/', $random_lib->generateRandomString());
    }
  }

}
