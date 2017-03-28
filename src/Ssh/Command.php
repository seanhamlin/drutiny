<?php

namespace Drutiny\Ssh;

/**
 *
 */
class Command {
  protected $hostname;
  protected $user;
  protected $sshArgs = [
    '-o UserKnownHostsFile=/dev/null',
    '-o StrictHostKeyChecking=no',
    '-o LogLevel=ERROR',
  ];

  /**
   *
   */
  public function __construct($user, $hostname) {
    $this->user = $user;
    $this->hostname = $hostname;
  }

  /**
   *
   */
  public function setArgument($value) {
    $this->sshArgs[] = $value;
    return $this;
  }

  /**
   *
   */
  protected function prepareSshCommand($command) {
    return sprintf("ssh %s %s@%s \"%s\"",
      implode(' ', $this->sshArgs),
      $this->user,
      $this->hostname,
      $command
    );
  }

  /**
   *
   */
  public function execute($command) {
    return $this->prepareSshCommand($command);
  }

}
