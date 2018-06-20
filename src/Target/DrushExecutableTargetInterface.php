<?php

namespace Drutiny\Target;

interface DrushExecutableTargetInterface extends TargetInterface {
  public function runDrushCommand($method, array $args, array $options, $pipe = '');
}
