<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractComparison;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Evaluate a PHP ini setting.
 * @Param(
 *  name = "setting",
 *  description = "The name of the ini setting to check.",
 *  type = "string"
 * )
 * @Param(
 *  name = "value",
 *  description = "The local value of the ini setting to compare for.",
 *  type = "mixed"
 * )
 * @Param(
 *  name = "comp_type",
 *  description = "The comparison operator to use for the comparison.",
 *  type = "string"
 * )
 * @Token(
 *  name = "local_value",
 *  description = "The local value from the php.ini file.",
 *  type = "mixed"
 * )
 */
class IniGet extends AbstractComparison {

  /**
   *
   */
  public function audit(Sandbox $sandbox)
  {
    $ini = $sandbox->drush()->evaluate(function () {
      return ini_get_all();
    });
    $setting = $sandbox->getParameter('setting');

    if (!isset($ini[$setting])) {
      return FALSE;
    }
    
    $sandbox->setParameter('local_value', $ini[$setting]['local_value']);

    return $this->compare($sandbox->getParameter('value'), $ini[$setting]['local_value'], $sandbox);
  }

}
