<?php

namespace Drutiny\AuditResponse;

use Drutiny\Policy;
use Drutiny\Audit;

/**
 * Class AuditResponse.
 *
 * @package Drutiny\AuditResponse
 */
class AuditResponse {

  protected $info;

  protected $state = Audit::NOT_APPLICABLE;

  protected $remediated = FALSE;

  protected $warning = FALSE;

  protected $tokens = [];

  /**
   * AuditResponse constructor.
   *
   * @param mixed $state
   *   A bool|int|null indicating the outcome of a Drutiny\Check\Check.
   */
  public function __construct(Policy $info) {
    $this->info = $info;
  }

  /**
   * Set the state of the response.
   */
  public function set($state = NULL, array $tokens) {
    switch (TRUE) {
      case ($state === Audit::SUCCESS):
      case ($state === Audit::PASS):
        $state = Audit::SUCCESS;
        break;

      case ($state === Audit::FAILURE):
      case ($state === Audit::FAIL):
        $state = Audit::FAIL;
        break;

      case ($state === Audit::NOT_APPLICABLE):
      case ($state === NULL):
        $state = Audit::NOT_APPLICABLE;
        break;

      case ($state === Audit::WARNING):
      case ($state === Audit::WARNING_FAIL):
      case ($state === Audit::NOTICE):
      case ($state === Audit::ERROR):
        // Do nothing. These are all ok.
        break;

      default:
        throw new AuditResponseException("Unknown state set in Audit Response: $state");
    }

    $this->state = $state;

    // Cannot support array and object tokens.
    foreach ($tokens as $key => $value) {
      if (is_array($value) || is_object($value)) {
        unset($tokens[$key]);
      }
    }
    
    $this->tokens = $tokens;
  }

  /**
   * Get the name.
   *
   * @return string
   *   The policy name.
   */
  public function getName() {
    return $this->info->get('name', $this->tokens);
  }

  /**
   * Get the title.
   *
   * @return string
   *   The checks title.
   */
  public function getTitle() {
    return $this->info->get('title', $this->tokens);
  }

  /**
   * Get the description for the check performed.
   *
   * @return string
   *   Translated description.
   */
  public function getDescription() {
    return $this->info->get('description', $this->tokens);
  }

  /**
   * Get the remediation for the check performed.
   *
   * @return string
   *   Translated description.
   */
  public function getRemediation() {
    return $this->info->has('remediation') ? $this->info->get('remediation', $this->tokens) : '';
  }

  /**
   * Get the failure message for the check performed.
   *
   * @return string
   *   Translated description.
   */
  public function getFailure() {
    return $this->info->has('failure') ? $this->info->get('failure', $this->tokens) : '';
  }

  /**
   * Get the success message for the check performed.
   *
   * @return string
   *   Translated description.
   */
  public function getSuccess() {
    return $this->info->get('success', $this->tokens);
  }

  /**
   * Get the warning message for the check performed.
   *
   * @return string
   *   Translated description.
   */
  public function getWarning() {
    return $this->hasWarning() ? $this->info->get('warning', $this->tokens) : '';
  }

  /**
   *
   */
  public function isSuccessful() {
    return $this->state === Audit::SUCCESS || $this->remediated || $this->isNotice();
  }

  /**
   *
   */
  public function isNotice() {
    return $this->state === Audit::NOTICE;
  }

  /**
   *
   */
  public function hasWarning() {
    return $this->state === Audit::WARNING || $this->state === Audit::WARNING_FAIL;
  }

  public function isRemediated($set = NULL)
  {
    if (isset($set)) {
      $this->remediated = $set;
    }
    return $this->remediated;
  }

  /**
   * Get the response based on the state outcome.
   *
   * @return string
   *   Translated description.
   */
  public function getSummary() {
    $summary = [];
    switch (TRUE) {
      case ($this->state === Audit::NOT_APPLICABLE):
        $summary[] = "This policy is not applicable to this site.";
        break;

      case ($this->state === Audit::ERROR):
        $summary[] = strtr('Could not determine the state of ' . $this->getTitle() . ' due to an error:
```
exception
```', $this->tokens);
        break;

      case ($this->state === Audit::WARNING):
        $summary[] = $this->getWarning();
      case ($this->state === Audit::SUCCESS):
      case ($this->state === Audit::PASS):
      case ($this->state === Audit::NOTICE):
        $summary[] = $this->getSuccess();
        break;

      case ($this->state === Audit::WARNING_FAIL):
        $summary[] = $this->getWarning();
      case ($this->state === Audit::FAILURE):
      case ($this->state === Audit::FAIL):
        $summary[] = $this->getFailure();
        break;

      default:
        throw new AuditResponseException("Unknown AuditResponse state ({$this->state}). Cannot generate summary for '" . $this->getTitle() . "'.");
        break;
    }
    return implode(PHP_EOL, $summary);
  }
}
