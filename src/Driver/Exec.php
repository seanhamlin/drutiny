<?php

namespace Drutiny\Driver;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 *
 */
class Exec extends Driver implements ExecInterface {

  /**
   * @inheritdoc
   */
  public function exec($command, $args = []) {
    $args['%docroot'] = '';
    $command = strtr($command, $args);
    $process = new Process($command);

    $this->log($command);
    $process->run();

    // Executes after the command finishes.
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $this->log($process->getOutput());

    return $process->getOutput();
  }

}
