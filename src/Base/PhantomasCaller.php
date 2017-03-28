<?php

namespace Drutiny\Base;

use Drutiny\Executor\ExecutorInterface;

/**
 *
 */
class PhantomasCaller {
  protected $executor;
  protected $drush;

  protected $domain = NULL;
  protected $metrics = NULL;
  protected $urls = [];

  /**
   *
   */
  public function __construct(ExecutorInterface $executor, DrushCaller $drush) {
    $this->executor = $executor;
    $this->drush = $drush;
  }

  /**
   * Mutator for the domain proeprty.
   *
   * @param string $domain
   *   The domain for the given drush alias.
   *
   * @return $this
   *   The PhantomasCaller.
   */
  public function setDomain($domain) {
    // @todo make the URL protocol configurable.
    if (strpos($domain, 'http') !== 0) {
      $domain = 'https://' . $domain;
    }
    $this->domain = $domain;
    return $this;
  }

  /**
   * Accessor for the domain property.
   *
   * @return string
   *   The configured domain for the alias.
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   *
   */
  public function setUrls($urls) {
    $this->urls = $urls;
    return $this;
  }

  /**
   *
   */
  public function setDrush(DrushCaller $drush) {
    $this->drush = $drush;
    return $this;
  }

  /**
   * Get authentication details for the request.
   */
  public function getAuth() {

    // Check for the presence of shield as this will potentially block
    // phantomas.
    if ($this->drush->isShieldEnabled()) {
      $username = $this->drush->getVariable('shield_user', '');
      $password = $this->drush->getVariable('shield_pass', '');
    }

    // Allow users to set environment variables as well if the HTTP
    // authentication is hard coded in settings.php for example. You can set
    // these by:
    //

    // export SITE_AUDIT_HTTP_AUTH_USER=[USERNAME]
    // export SITE_AUDIT_HTTP_AUTH_PASS=[PASSWORD]
    else if (!empty(getenv('SITE_AUDIT_HTTP_AUTH_USER'))) {
      $username = getenv('SITE_AUDIT_HTTP_AUTH_USER');
      $password = getenv('SITE_AUDIT_HTTP_AUTH_PASS');
    }

    return !empty($username) && !empty($password) ? [$username, $password] : FALSE;
  }

  public function getMetrics($url = '/') {
    $command = ['phantomas'];
    $command[] = '"' . $this->domain . $url . '"';
    $command[] = '--ignore-ssl-errors';
    $command[] = '--reporter=json';
    $command[] = '--timeout=30';

    // Remove a lot of output that we don't need at the moment.
    $command[] = '--skip-modules=domMutations,domQueries,domHiddenContent,domComplexity,jQuery';

    if ($auth = $this->getAuth()) {
      list($username, $password) = $auth;
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
