<?php

namespace Drutiny\Driver;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 *
 */
trait DrushTrait {

  protected $drushOptions = [];

  protected $globalDefaults = [];

  public function getAlias() {
    return $this->alias;
  }

  /**
   * Converts into method into a Drush command.
   */
  public function __call($method, $args) {
    $this->setDrushOptions($this->getGlobalDefaults());

    // Convert method from camelCase to Drush hyphen based method naming.
    // E.g. PmInfo will become pm-info.
    preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches);
    $method = implode('-', array_map('strtolower', $matches[0]));
    try {
      $output = $this->runCommand($method, $args);
    }
    catch (ProcessFailedException $e) {
      $this->sandbox()->logger()->info($e->getProcess()->getOutput());
      throw new DrushFormatException("Drush command failed.", $e->getProcess()->getOutput());
    }

    if (in_array("--format='json'", $this->drushOptions)) {
      if (!$json = json_decode($output, TRUE)) {
        throw new DrushFormatException("Cannot parse json output from drush: $output", $output);
      }
      $output = $json;
    }

    // Reset drush options.
    $this->drushOptions = [];

    return $output;
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
    $this->drushOptions[] = '--format=json';
    $modules = $this->__call('pmList', []);
    if (!$modules = json_decode($modules, TRUE)) {
      throw new DrushFormatException("Cannot parse json output from drush: $modules", $modules);
    }

    // Reset drush options.
    $this->drushOptions = [];
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

    if ($index = array_search('--format=json', $this->drushOptions)) {
      unset($this->drushOptions[$index]);
    }
    $this->drushOptions[] = '--format=yaml';
    $this->drushOptions[] = '-y';

    $pipe = "echo '$value' | base64 --decode |";

    $this->runCommand('config-set', [
      $collection, $key, '-'
    ], $pipe);

    $this->drushOptions = [];
    return TRUE;
  }

  /**
   * Override for drush command 'sqlq'.
   */
  public function sqlq($sql) {
    $args = ['--db-prefix', '"' . $sql . '"'];
    $output = $this->__call('sqlq', $args);
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
   *
   */
  public function runCommand($method, $args, $pipe = '') {
    return $this->sandbox()->exec('@pipe drush @options @method @args', [
      '@method' => $method,
      '@args' => implode(' ', $args),
      '@options' => implode(' ', $this->drushOptions),
      '@pipe' => $pipe
    ]);
  }

  /**
   *
   */
  public function setDrushOptions(array $options) {
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
      if (!in_array($option, $this->drushOptions)) {
        $this->drushOptions[] = $option;
      }
    }
    return $this;
  }

  /**
   * Set an option that will be presented on every drush command.
   */
  public function setGlobalDefaultOption($key, $value) {
    $this->globalDefaults[$key] = $value;
    return $this;
  }

  /**
   * Get an option that will be presented on every drush command.
   */
  public function getGlobalDefaultOption($key) {
    return isset($this->globalDefaults[$key]) ? $this->globalDefaults[$key] : FALSE;
  }

  /**
   * Remove global option.
   */
  public function removeGlobalDefaultOption($key) {
    unset($this->globalDefaults[$key]);
    return $this;
  }

  /**
   * Retrieve global defaults.
   */
  public function getGlobalDefaults() {
    return $this->globalDefaults;
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
    $execution[] = 'f=`tempfile`';
    $execution[] = "echo $transfer | base64 --decode > \$f";

    $pipe = implode(';' . PHP_EOL, $execution);

    $this->setDrushOptions($this->getGlobalDefaults());
    $execution[] = strtr('drush @alias @options scr $f', [
      '@options' => implode(' ', $this->drushOptions),
      '@alias' => $this->alias,
    ]);
    $execution[] = 'rm $f';

    $transfer = implode(';' . PHP_EOL, $execution);


    // $transfer = "echo $transfer | base64 --decode | bash";

    $output = $this->sandbox()->exec($transfer);
    return json_decode($output, TRUE);
  }

}
