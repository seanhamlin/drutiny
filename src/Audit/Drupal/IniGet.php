<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractComparison;
use Drutiny\Sandbox\Sandbox;

/**
 * Evaluate a PHP ini setting.
 */
class IniGet extends AbstractComparison {

  /**
   *
   */
  public function audit(Sandbox $sandbox)
  {
    $setting = $sandbox->getParameter('setting');
    $command = strtr("echo json_encode(ini_get('%setting'));", [
      '%setting' => $setting
    ]);
    $command = "\"$command\"";
    $value = $sandbox->drush(['format' => 'json'])->ev($command);

    return $this->compare($sandbox->getParameter('value'), $value, $sandbox);
  }

}
