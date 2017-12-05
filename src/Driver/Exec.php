<?php

namespace Drutiny\Driver;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drutiny\Cache;

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

    if ($output = Cache::get('exec', $command)) {
      $this->log("cache hit for: $command");
      return $output;
    }

    $process = new Process($command);
    $process->setTimeout(600);

    $this->log($command);
    $process->run();

    // Executes after the command finishes.
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $output = $process->getOutput();

    $this->log($output);
    Cache::set('exec', $command, $output);

    return $output;
  }

}
