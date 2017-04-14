<?php

namespace Drutiny\Check\D8;

use Drutiny\Check\Check;
use Drutiny\Check\RemediableInterface;
use Drutiny\Sandbox\Sandbox;

/**
 * Shield disabled.
 */
class ShieldDisabled extends Check implements RemediableInterface {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {

    try {
      $info = $sandbox->drush(['format' => 'json'])
        ->pmInfo('shield');
      if ($info['shield']['status'] == "not installed") {
        return TRUE;
      }

      $config = $sandbox->drush(['format' => 'json'])
        ->configGet('shield.settings', 'user');
      return (bool) $config['shield.settings:user'];
    }
    // If the module is not present in the code base, an error will be thrown.
    catch (\Exception $e) {
      return TRUE;
    }
  }

  /**
   * @inheritdoc
   */
  public function remediate(Sandbox $sandbox) {
    $sandbox->drush()
      ->pmUninstall('-y', 'shield');
    return $this->check($sandbox);
  }

}
