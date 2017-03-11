<?php

namespace Drutiny\Executor;

/**
 *
 */
class Result {
  protected $command;
  protected $output;
  protected $return_val;

  /**
   *
   */
  public function __construct($command) {
    $output = [];
    $return_val = 0;
    // Suppress StdErr output.
    $dev_null = ' 2> /dev/null';
    if (stripos(PHP_OS, 'win') === 0) {
      $dev_null = ' 2> nul';
    }
    $command .= $dev_null;
    exec($command, $output, $return_val);

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

    $this->command = $command;
    $this->output = $output;
    $this->return_val = $return_val;

    if (!$this->isSuccessful()) {
      throw new ResultException("Command failed: $command", $this);
    }
  }

  /**
   *
   */
  public function __toString() {
    return trim(implode(PHP_EOL, array_filter($this->output)));
  }

  /**
   *
   */
  public function isSuccessful() {
    return $this->return_val === 0;
  }

  /**
   *
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   *
   */
  public function getText() {
    return (string) $this;
  }

  /**
   *
   */
  public function parseJson($assoc = FALSE) {
    return json_decode((string) $this, $assoc);
  }

}
