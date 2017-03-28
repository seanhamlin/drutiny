<?php
/**
 * @file
 * Contains Drutiny\Check\Security\Headers
 */

namespace Drutiny\Check\Security;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Base\PhantomasCaller;
use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;
use Drutiny\Helpers\Text;
use GuzzleHttp\Client;

/**
 * @CheckInfo(
 *   title = "Response Headers",
 *   description = "Ensure the site has appropriate response headers.",
 *   remediation = "Enable seckit and configure to enable headers.",
 *   success = "All response headers are configured correctly.",
 *   failure = "<code>:num</code> errors found with response headers: <ul><li>:reasons</li></ul>",
 *   exception = "Unable to determine response headers.",
 *   not_available = "Unable to determine response headers.",
 * )
 */
class Headers extends Check {

  /**
   * Build a new Guzzle client.
   *
   * @return \GuzzleHttp\Client
   */
  public function getClient() {
    return new Client();
  }

  /**
   * Access the Phantom runner.
   *
   * @return Drutiny\Base\PhantomasCaller;
   */
  public function getPhantom() {
    return $this->context->phantomas;
  }

  /**
   * Determine if the site response headers are configured correctly.
   */
  public function check() {

    $client = $this->getClient();
    $request_headers = [];
    $errors = [];

    // If Shield is enabled - add the credentials to the headers.
    if ($auth = $this->getPhantom()->getAuth()) {
      $request_headers['auth'] = $auth;
    }

    $headers = $this->getOption('expected_headers', []);

    if ($this->getOption('defaults', TRUE)) {
      $headers = array_merge([
        'x-content-options' => 'Should be set to <code>nosniff</code> to prevent clickjacking',
        'x-frame-options' => 'Should be set to <code>SAMEORIGIN</code> to prevent clickjacking',
        'x-xss-protection' => 'Should be set to prevent clickjacking',
        'content-security-policy' => 'Should be set to prevent XSS vulnerabilities',
      ], $headers);
    }

    if (empty($headers)) {
      return AuditResponse::AUDIT_NA;
    }

    // Build the request.
    $response = $client->request('GET', $this->getPhantom()->getDomain(), $request_headers);

    foreach ($headers as $header => $suggested) {
      if (empty($response->getHeader($header))) {
        $errors[] = Text::translate('<strong>:header</strong> :message', [
          ':header' => $header,
          ':message' => $suggested,
        ]);
      }
    }

    if (empty($errors)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    $this->setToken('num', count($errors));
    $this->setToken('reasons', implode('</li><li>', $errors));

    return AuditResponse::AUDIT_FAILURE;
  }

}
