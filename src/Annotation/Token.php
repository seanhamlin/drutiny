<?php

namespace Drutiny\Annotation;

/**
 * @Annotation
 */
class Token {
  public $description;
  public $name;
  public $type;
  public $default;

  public function toArray() {
    return [
      'description' => $this->description,
      'type' => $this->type,
      'default' => $this->default,
    ];
  }
}
