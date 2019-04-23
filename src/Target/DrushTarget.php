<?php

namespace Drutiny\Target;

use Drutiny\Driver\Exec;
use Drutiny\Container;

/**
 * @Drutiny\Annotation\Target(
 *  name = "drush"
 * )
 */
class DrushTarget extends Target implements DrushTargetInterface, DrushExecutableTargetInterface {
  use DrushTargetMetadataTrait;

  protected $alias;

  /**
   * @inheritdoc
   * Implements Target::parse().
   */
  public function parse($target_data) {
    $this->alias = $target_data;

    // Get some information from the local site-alias.
    $proc = new Exec();
    $data = $proc->exec('drush sa @alias --format=json', [
      '@alias' => $target_data,
    ]);
    $options = json_decode($data, TRUE);

    $key = str_replace('@', '', $target_data);
    $this->options = isset($options[$key]) ? $options[$key] : array_shift($options);

    // Set the URI from the Drush alias if it hasn't been manually set already.
    if (!$this->uri() && isset($this->options['uri'])) {
      $this->setUri($this->options['uri']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlias() {
    return $this->alias;
  }

  /**
   * {@inheritdoc}
   */
  public function runDrushCommand($method, array $args, array $options, $pipe = '', $bin = 'drush') {
    $parameters = [
      '@method' => $method,
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $options),
      '@alias' => $this->getAlias(),
      '@pipe' => $pipe,
      '@drush' => $bin,
    ];

    // Do not use a locally sourced alias if the command will be executed remotely.
    if (isset($this->options['remote-host'])) {
      $parameters['@root'] = $this->options['root'];
      return $this->exec('@pipe @drush -r @root @options @method @args', $parameters);
    }
    return $this->exec('@pipe @drush @alias @options @method @args', $parameters);
  }

  /**
   * @inheritdoc
   * Implements ExecInterface::exec().
   */
  public function exec($command, $args = [])
  {
    // If the drush target is remote, amend the command
    // to execute the command remotely.
    if (isset($this->options['remote-host'])) {
      $args['%docroot%'] = $this->options['root'];
      $command = strtr($command, $args);
      Container::getLogger()->info(__CLASS__ . ": base64 encoding command: $command");
      $command = base64_encode($command);
      $command = "echo $command | base64 --decode | sh";

      $defaults = $this->options + [
        'remote-user' => get_current_user(),
        'remote-host' => '',
        'ssh-options'  => '',
      ];
      unset($defaults['path-aliases']);
      $args = ['@command' => escapeshellarg($command)];

      $command = strtr('ssh ssh-options remote-user@remote-host @command', $defaults);
    }
    return parent::exec($command, $args);
  }
}
