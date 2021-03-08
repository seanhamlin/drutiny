<?php

namespace Drutiny\Target;

/**
 * Definition of a Target.
 */
interface TargetSourceInterface
{

  /**
   * Get a list of available targets.
   */
    public function getAvailableTargets():array;
}
