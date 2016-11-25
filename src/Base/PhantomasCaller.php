<?php

namespace SiteAudit\Base;

use SiteAudit\Executor\ExecutorInterface;

class PhantomasCaller {
  protected $executor;

  protected $domain = NULL;
  protected $metrics = NULL;
  protected $urls = [];

  public function __construct(ExecutorInterface $executor) {
    $this->executor = $executor;
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

  public function getMetrics($url = '/') {
    $command = ['phantomas'];
    $command[] = '"' . $this->domain . $url . '"';
    $command[] = '--ignore-ssl-errors';
    $command[] = '--reporter=json';
    $command[] = '--timeout=30';
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
