<?php

namespace Drutiny\Driver;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drutiny\Container;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\DrushTargetInterface;
use Drutiny\Target\DrushExecutableTargetInterface;
use Drutiny\Target\TargetInterface;

/**
 *
 */
class DrushDriver {
  /**
   * @array options to pass to Drush.
   */
  protected $options = [];

  /**
   * @string path to drush.
   */
  protected $drushBin;

  /**
   * @string drush site-alias.
   */
  protected $alias;

  /**
   * @param TargetInterface.
   */
  protected $target;

  public function __construct(TargetInterface $target, $drush_bin, $options = [], $alias = '@self')
  {
    $this->drushBin = $drush_bin;
    $this->target  = $target;
    $this->setOptions($options)
         ->setOptions(['uri' => $target->uri()]);
    $this->alias = $alias;
  }

  /**
   * Instansiate a new Drush Driver instance.
   *
   * @param TargetInterface $target
   * @param string $drush_bin path to drush executable.
   */
  public static function createFromTarget(TargetInterface $target, $drush_bin = 'drush')
  {
    $options = [];
    $alias = '@self';
    if ($target instanceof DrushTargetInterface) {
      $alias = $target->getAlias();
    }
    return new static($target, $drush_bin, $options, $alias);
  }

  /**
   * Get drush site-alias.
   */
  public function getAlias() {
    return $this->alias;
  }

  /**
   * Converts into method into a Drush command.
   */
  public function __call($method, $args) {
    // Convert method from camelCase to Drush hyphen based method naming.
    // E.g. PmInfo will become pm-info.
    preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches);
    $method = implode('-', array_map('strtolower', $matches[0]));
    try {
      $output = $this->runCommand($method, $args);
    }
    catch (ProcessFailedException $e) {
      Container::getLogger()->info($e->getProcess()->getOutput());
      throw new DrushFormatException("Drush command failed: $method", $e->getProcess()->getOutput());
    }

    if (in_array("--format='json'", $this->getOptions())) {
      if (!$json = json_decode($output, TRUE)) {
        throw new DrushFormatException("Cannot parse json output from drush: $output", $output);
      }
      $output = $json;
    }

    return $output;
  }

  /**
   * Run the drush command.
   */
  protected function runCommand($method, $args, $pipe = '') {
    // Hand task off to Target directly if it supports it.
    if ($this->target instanceof DrushExecutableTargetInterface) {
      return $this->target->runDrushCommand($method, $args, $this->getOptions(), $pipe);
    }
    return $this->target->exec('@pipe @bin @alias @options @method @args', [
      '@method' => $method,
      '@alias' => $this->getAlias(),
      '@bin' => $this->drushBin,
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $this->getOptions()),
      '@pipe' => $pipe
    ]);
  }

  public function helper()
  {
    return new DrushHelper($this);
  }

  /**
   * Get drush options.
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   * Set drush options.
   */
  public function setOptions(array $options) {
    foreach ($options as $key => $value) {
      if (is_int($key)) {
        $option  = '--' . $value;
      }
      elseif (strlen($key) == 1) {
        $option = '-' . $key;
        if (!empty($value)) {
          $option .= ' ' . escapeshellarg($value);
        }
      }
      else {
        $option = '--' . $key;
        if (!empty($value)) {
          $option .= '=' . escapeshellarg($value);
        }
      }
      if (!in_array($option, $this->options)) {
        $this->options[] = $option;
      }
    }
    return $this;
  }

  /**
   * Clean up the output of Drush.
   *
   * @param $output
   * @return array
   */
  private function cleanOutput($output) {
    if (!is_array($output)) {
      $output = explode(PHP_EOL, $output);
    }

    // Datetime weirdness. Apparently this is caused by theming issues on the
    // remote theme. Why it is being called when executed via CLI is another
    // story.
    foreach ($output as $key => $value) {
      $invalid_strings = [
        'date_timezone_set() expects parameter',
        'date_format() expects parameter',
        'common.inc:20',
        'given common.inc:20',
        'Warning: Using a password on the command line interface can be insecure.',
      ];
      foreach ($invalid_strings as $invalid_string) {
        if (strpos($value, $invalid_string) === 0) {
          unset($output[$key]);
        }
      }
    }

    // Remove blank lines.
    $output = array_filter($output);

    // Ensure we are returning arrays with no key association.
    return array_values($output);
  }

  /**
   * Determine if a module is enabled or not.
   *
   * @param $name
   *   The machine name of the module to check.
   * @return bool
   *   Whether the module is enabled or not.
   *
   * @throws DrushFormatException
   */
  public function moduleEnabled($name) {
    $this->options[] = '--format=json';
    $modules = $this->__call('pmList', []);
    if (!$modules = json_decode($modules, TRUE)) {
      throw new DrushFormatException("Cannot parse json output from drush: $modules", $modules);
    }
    $this->options = [];
    return isset($modules[$name]) && $modules[$name]['status'] === 'Enabled';
  }

  /**
   * Override config-set to allow better value setting.
   *
   * @param $collection
   * @param $key
   * @param $value
   * @return bool
   */
  public function configSet($collection, $key, $value) {
    $value = base64_encode(Yaml::dump($value));

    if ($index = array_search('--format=json', $this->options)) {
      unset($this->options[$index]);
    }
    $this->options[] = '--format=yaml';
    $this->options[] = '-y';

    $pipe = "echo '$value' | base64 --decode |";

    $this->runCommand('config-set', [
      $collection, $key, '-'
    ], $pipe);

    return TRUE;
  }

  /**
   * Override for drush command 'sqlq'.
   */
  public function sqlq($sql) {
    // $args = ['--db-prefix', '"' . $sql . '"'];
    $sql = strtr($sql, [
      "{" => '',
       "}" => ''
     ]);
    $output = trim($this->__call('sqlq', ['"' . $sql . '"']));
    $output = $this->cleanOutput($output);
    return $output;
  }

  /**
   * Override for drush command 'sql-query'.
   */
  public function sqlQuery($sql) {
    return $this->sqlq($sql);
  }

  /**
   * This function takes PHP in this execution scope (Closure) and executes it
   * against the Drupal target using Drush php-script.
   *
   * @param \Closure $callback
   * @param array $args
   * @return mixed
   */
  public function evaluate(\Closure $callback, Array $args = []) {
    $args = array_values($args);
    $func = new \ReflectionFunction($callback);
    $filename = $func->getFileName();
    $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $func->getEndLine();
    $length = $end_line - $start_line;

    $source = file($filename);
    $body = array_slice($source, $start_line, $length);

    $col = strpos($body[0], 'function');
    $body[0] = substr($body[0], $col);

    $last = count($body) - 1;
    $col = strpos($body[$last], '}') + 1;
    $body[$last] = substr($body[$last], 0, $col);

    $code = ['<?php'];
    $calling_args = [];
    foreach ($func->getParameters() as $i => $param) {
      $code[] = '$' . $param->name . ' = ' . var_export($args[$i], TRUE) . ';';
      $calling_args[] = '$' . $param->name;
    }

    $code[] = '$evaluation = ' . implode("", $body) . ';';
    $code[] = 'echo json_encode($evaluation(' . implode(', ', $calling_args) . '));';

    $transfer = base64_encode(implode(PHP_EOL, $code));

    $execution = [];
    // Linux uses tempfile while OSX uses mktemp.
    $execution[] = 't=`which tempfile || which mktemp`; f=$($t)';
    $execution[] = "echo $transfer | base64 --decode > \$f";

    $pipe = implode(';' . PHP_EOL, $execution);

    $execution[] = strtr('drush @alias @options scr $f', [
      '@options' => implode(' ', $this->getOptions()),
      '@alias' => $this->getAlias(),
    ]);
    $execution[] = 'rm $f';

    $transfer = implode(';' . PHP_EOL, $execution);

    $output = $this->target->exec($transfer);
    return json_decode($output, TRUE);
  }

}
