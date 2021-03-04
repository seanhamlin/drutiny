<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\CloudApiV2;
use Drutiny\Audit\AbstractAnalysis;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Adds the contents of composer.lock to the dataBag.
 */
class ComposerAnalysis extends AbstractAnalysis {

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    try {
      $composer_info = $this->target->getService('exec')->run('cat $DRUSH_ROOT/../composer.lock || cat $DRUSH_ROOT/composer.lock || echo "[]"' , function($output){
        return json_decode($output, true);
      });
    }
    catch (ProcessFailedException $e) {
      $composer_info = [];
    }

    $this->set('has_composer_lock', is_array($composer_info) && !empty($composer_info));

    if (!is_array($composer_info)) {
      return;
    }

    foreach ($composer_info as $key => $value) {
      $this->set($key, $value);
    }

  }

}
