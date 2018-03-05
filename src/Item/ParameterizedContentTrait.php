<?php
namespace Drutiny\Item;

trait ParameterizedContentTrait {
  /**
   * @array parameters to tailor the audit carried out.
   *
   * Also used as token replacements.
   */
  protected $parameters = [];

  public function addParameter($name, $info) {
    $default = [
      'default' => FALSE,
      'description' => '',
    ];

    $info = $default + $info;

    if (!isset($info['type'])) {
      $info['type'] = gettype($info['default']);
    }
    $this->parameters[$name] = $info;
  }

  /**
   * Pull default values from each parameter.
   */
  public function getParameterDefaults()
  {
      $defaults = [];
      foreach ($this->parameters as $name => $info) {
        $defaults[$name] = isset($info['default']) ? $info['default'] : null;
      }
      return $defaults;
  }
}
 ?>
