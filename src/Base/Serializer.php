<?php
/**
 * @file
 * Contains SiteAudit\Base\Serializer
 */

namespace SiteAudit\Base;

/**
 * Class Serializer
 *
 * Attempts to repair broken serialised strings.
 *
 * @package SiteAudit\Base
 */
class Serializer {
  /**
   * Attempt to unserialize data from the DB.
   *
   * Sometimes the data serialized for field settings doesn't unserialize
   * correctly when it is returned by MySQL. We will attempt to fix a broken
   * serialised string before returning.
   *
   * @param string $data
   *
   * @return array|bool
   *   Unserialised data or false.
   */
  public static function unserialize($data) {
    // Suppress warnings from erroneous serialized data.
    $data = @unserialize($data);

    // If unserialize fails $data === FALSE.
    if ($data !== FALSE) {
      return $data;
    }

    // Attempt to recalculate the length of each part of the serialised string.
    $data = preg_replace_callback(
      '/(?<=^|\{|;)s:(\d+):\"(.*?)\";(?=[asbdiO]\:\d|N;|\}|$)/s',
      function($m){
        return 's:' . mb_strlen($m[2]) . ':"' . $m[2] . '";';
      },
      $data
    );

    // Will === FALSE if we still can't unserialise.
    return @unserialize($data);
  }
}
