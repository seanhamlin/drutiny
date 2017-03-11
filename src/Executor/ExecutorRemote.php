<?php

namespace Drutiny\Executor;

use Drutiny\Ssh\Command as SshCommand;

/**
 *
 */
class ExecutorRemote extends Executor {
  protected $user = 'root';
  protected $hostname = 'no.server.set';
  protected $args = [];

  /**
   *
   */
  public function setRemoteUser($user) {
    $this->user = $user;
    return $this;
  }

  /**
   *
   */
  public function setRemoteHost($hostname) {
    $this->hostname = $hostname;
    return $this;
  }

  /**
   *
   */
  public function setArgument($value) {
    $this->args[] = $value;
  }

  /**
   *
   */
  public function execute($command) {
    $ssh = new SshCommand($this->user, $this->hostname);
    foreach ($this->args as $arg) {
      $ssh->setArgument($arg);
    }
    $command = $ssh->execute($command);
    return parent::execute($command);
  }

}
