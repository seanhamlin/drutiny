<?php
/**
 * @file
 * Contains Drutiny\Check\Security\Headers
 */

namespace Drutiny\Check\Security;

use Drutiny\AuditResponse\AuditResponse;
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
   * Determine if the site response headers are configured correctly.
   */
  public function check() {

    $client = new Client();
    $headers = [];
    $errors = [];

    // If Shield is enabled - add the credentials to the headers.
    if ($auth = $this->context->phantomas->getAuth()) {
      $headers['auth'] = $auth;
    }

    $response = $client->request('GET', $this->context->phantomas->getDomain(), $headers);

    $expected = [
      'x-content-options' => 'Should be set to <code>nosniff</code> to prevent clickjacking',
      'x-frame-options' => 'Should be set to <code>SAMEORIGIN</code> to prevent clickjacking',
      'x-xss-protection' => 'Should be set to prevent clickjacking',
      'content-security-policy' => 'Should be set to prevent XSS vulnerabilities',
    ];

    $expected = $this->getOption('expected_headers', $expected);

    foreach ($expected as $header => $suggested) {
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
