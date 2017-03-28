<?php

namespace Drutiny\Base;

/**
 * Class String.
 *
 * Useful functions on strings.
 */
class StringLib {

  /**
   * Strip comments from a file.
   *
   * @param string $contents
   *   The contents of a PHP file.
   * @return string
   */
  public static function stripComments($contents) {
    $trimmed = [];
    $lines = explode("\n", $contents);
    foreach($lines as $index => $line) {
      // Exclude '<?php'
      if (strpos($line, '<?php') === 0) {
        continue;
      }
      // Exclude comment only lines.
      if (preg_match('/^\s*\/\/.*/', $line)) {
        continue;
      }
      // Exclude comment only lines.
      if (preg_match('/^\s*#.*/', $line)) {
        continue;
      }

      $trimmed[] = $line;
    }

    // Remove empty lines;
    $trimmed = array_filter($trimmed);

    return implode("\n", $trimmed);
  }

}
