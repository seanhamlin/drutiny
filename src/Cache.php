<?php

namespace Drutiny;

/**
 * A static cache handler.
 */
class Cache {

  static protected $cache = [];

  static public function set($bin, $cid, $value) {
    self::$cache[$bin][$cid] = $value;
    return TRUE;
  }

  static public function get($bin, $cid) {
    if (!isset(self::$cache[$bin][$cid])) {
      return FALSE;
    }
    return self::$cache[$bin][$cid];
  }

  static public function purge($bin = NULL) {
    if ($bin) {
      self::$cache[$bin] = [];
    }
    else {
      self::$cache = [];
    }
    return TRUE;
  }

  static public function delete($bin, $cid) {
    unset(self::$cache[$bin][$cid]);
    return TRUE;
  }

}
