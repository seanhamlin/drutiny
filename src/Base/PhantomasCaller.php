<?php

namespace Drutiny\Base;

use Drutiny\Base\DrushCaller;
use Drutiny\Executor\ExecutorInterface;

class PhantomasCaller {
  protected $executor;
  protected $drush;

  protected $domain = NULL;
  protected $metrics = NULL;
  protected $urls = [];

  public function __construct(ExecutorInterface $executor, DrushCaller $drush) {
    $this->executor = $executor;
    $this->drush = $drush;
  }

  public function setDomain($domain) {
    // @todo make the URL protocol configurable.
    if (strpos($domain, 'http') !== 0) {
      $domain = 'https://' . $domain;
    }
    $this->domain = $domain;
    return $this;
  }

  public function setUrls($urls) {
    $this->urls = $urls;
    return $this;
  }

  public function setDrush(DrushCaller $drush) {
    $this->drush = $drush;
    return $this;
  }

  public function getMetrics($url = '/') {
    $command = ['phantomas'];
    $command[] = '"' . $this->domain . $url . '"';
    $command[] = '--ignore-ssl-errors';
    $command[] = '--reporter=json';
    $command[] = '--timeout=30';

    // Remove a lot of output that we don't need at the moment.
    $command[] = '--skip-modules=domMutations,domQueries,domHiddenContent,domComplexity,jQuery';

    // Check for the presence of shield as this will potentially block
    // phantomas.
    if ($this->drush->isShieldEnabled()) {
      $username = $this->drush->getVariable('shield_user', '');
      $password = $this->drush->getVariable('shield_pass', '');
      $command[] = "--auth-user='$username'";
      $command[] = "--auth-pass='$password'";
    }

    // Allow users to set environment variables as well if the HTTP
    // authentication is hard coded in settings.php for example. You can set
    // these by:
    //
    // export DRUTINY_HTTP_AUTH_USER=[USERNAME]
    // export DRUTINY_HTTP_AUTH_PASS=[PASSWORD]
    else if (!empty(getenv('DRUTINY_HTTP_AUTH_USER'))) {
      $username = getenv('DRUTINY_HTTP_AUTH_USER');
      $password = getenv('DRUTINY_HTTP_AUTH_PASS');
      $command[] = "--auth-user='$username'";
      $command[] = "--auth-pass='$password'";
    }

    return $this->executor->execute(implode(' ', $command));
  }

  /**
   * Wipe metrics.
   */
  public function clearMetrics() {
    $this->metrics = NULL;
    return $this;
  }

  /**
   * Try to get a metric from Phantomas.
   *
   * @see https://github.com/macbre/phantomas for available metrics you can use.
   */
  public function getMetric($name = 'contentLength', $default = NULL) {
    try {
      // First time this is run, refresh the metrics list.
      if (is_null($this->metrics)) {
        $metrics = $this->getMetrics()->parseJson();
        $this->metrics = $metrics;
      }

      if (isset($this->metrics->metrics->{$name})) {
        return $this->metrics->metrics->{$name};
      }

      return $default;
    }
    catch (\Exception $e) {
      return $default;
    }
  }

  /**
   * Try to get a metric from Phantomas.
   *
   * @see https://github.com/macbre/phantomas for available metrics you can use.
   */
  public function getOffender($name = 'biggestResponse', $default = NULL) {
    try {
      // First time this is run, refresh the metrics list.
      if (is_null($this->metrics)) {
        $metrics = $this->getMetrics()->parseJson();
        $this->metrics = $metrics;
      }

      if (isset($this->metrics->offenders->{$name})) {
        return $this->metrics->offenders->{$name};
      }

      return $default;
    }
    catch (\Exception $e) {
      return $default;
    }
  }



}
