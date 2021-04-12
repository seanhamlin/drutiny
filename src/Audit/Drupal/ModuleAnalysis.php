<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is enabled check.
 *
 */
class ModuleAnalysis extends AbstractAnalysis
{

  /**
   * {@inheritDoc}
   */
    public function gather(Sandbox $sandbox)
    {
      $list = [];
      $bootstrapped = FALSE;

      // Drush must be able to bootstrap Drupal to run pm:list.
      if ($this->drupalBootstrap()) {
        $bootstrapped = TRUE;
        $list = $this->target->getService('drush')
          ->pmList([
            'format' => 'json',
            'type' => 'module',
          ])
          ->run(function ($output) {
            return json_decode($output, TRUE);
          });
      }
      $this->set('modules', $list);
      $this->set('bootstrapped', $bootstrapped);
    }

  /**
   * Determine if Drupal can be bootstrapped.
   *
   * @return bool
   *   True if Drush can bootstrap Drupal.
   */
  private function drupalBootstrap(): bool {
    $drush_status = $this->target->getService('drush')
      ->status([
        'format' => 'json',
      ])
      ->run(function ($output) {
        return json_decode($output, TRUE);
      });
    return isset($drush_status['bootstrap']);
  }
}
