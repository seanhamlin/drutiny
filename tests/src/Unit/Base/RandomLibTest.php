<?php

use Drutiny\Base\RandomLib;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drutiny\Base\RandomLib
 */
class RandomLibTest extends TestCase {

  const ITERATIONS = 100;

  /**
   * @covers ::generateRandomString
   * @group base
   */
  public function testGenerateRandomString() {
    $previous = [];

    // Ensure the generated string is alphanumeric only.
    for ($i = 0; $i < self::ITERATIONS; $i++) {
      $random = RandomLib::generateRandomString();
      $this->assertRegExp('/^[a-zA-Z0-9]{32}$/', $random);
      $previous[] = $random;
    }

    // Ensure all strings are unique.
    $previous = array_unique($previous);
    $this->assertEquals(count($previous), self::ITERATIONS);
  }

}
