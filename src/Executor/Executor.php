<?php

namespace Drutiny\Executor;

use Drutiny\Executor\ExecutorInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Executor implements ExecutorInterface {

  protected $io;

  public function __construct(SymfonyStyle $io) {
    $this->io = $io;
  }

  public function execute($command) {

    // Optional debug loggins.
    if ($this->io->isVerbose()) {
      $this->io->text($command);
    }

    return new Result($command);
  }
}
