<?php
/**
 * @file
 * Contains Drutiny\Base\String
 */

namespace Drutiny\Helpers;


class Text {

  /**
   * Translate a string.
   *
   * Helper method to translate a string. This is done so that we can do similar
   * replacements as drupal t().
   *
   * @param string $string
   *   String to replace.
   * @param array $replacements
   *   An array of replacements.
   *
   * @return string
   *   The string replaced.
   */
  public static function translate($string, $replacements = []) {
    return strtr($string, $replacements);
  }
}
