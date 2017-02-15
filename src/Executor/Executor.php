<?php

namespace Drutiny\Executor;

use Drutiny\Executor\ExecutorInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Executor implements ExecutorInterface {

  protected $output;

  public function __construct(OutputInterface $output) {
    $this->output = $output;
  }

  public function execute($command) {

    if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
      $this->output->writeln($command);
    }

    return new Result($command);
  }
}
