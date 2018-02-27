<?php

namespace Drutiny;

use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Drutiny\Item\Item;

/**
 *
 */
class Policy extends Item {
  use \Drutiny\Item\ContentSeverityTrait;
  use \Drutiny\Item\ParameterizedContentTrait;

  /**
   * @string A written recommendation of what remediation to take if the policy fails.
   */
  protected $remediation;

  /**
   * @param array $info
   */
  public function __construct(array $info) {
    foreach ($info as $key => $value) {
      if (!property_exists($this, $key)) {
        continue;
      }
      $this->{$key} = $value;
    }

    // Don't allow this value to be set by the policy $info.
    $this->maxSeverity = Audit::FAIL;
    if (isset($info['max_severity'])) {
      $this->setMaxSeverity($info['max_severity']);
    }

    $validator = Validation::createValidatorBuilder()
      ->addMethodMapping('loadValidatorMetadata')
      ->getValidator();

    $errors = $validator->validate($this);

  /**
   * @string A written failure message template. May contain tokens.
   */
  protected $failure;

  /**
   * @string A written warning message. May contain tokens.
   */
  protected $warning;

  /**
   * Render a property.
   *
   * @param $markdown
   * @param $replacements
   * @return string
   * @throws \Exception
   */
  private $remediable = FALSE;

  /**
   * Set the max severity of an audited outcome applicable to this policy.
   *
   * This ensures that an audit doesn't set a severity that doesn't match the
   * importance of the policy. This does not apply for Audit::ERROR or
   * Audit::NOT_APPLICABLE responses.
   *
   * @param bool $severity
   */
  public function setMaxSeverity($severity = Audit::FAIL) {
    if (is_string($severity)) {
      $severity = strtolower($severity);
    }
    switch (TRUE) {
      case $severity === 'warning':
      case $severity === 'warn':
        $this->maxSeverity = Audit::WARNING;
        break;

      case $severity === 'warning_fail':
        $this->maxSeverity = Audit::WARNING_FAIL;
        break;

      case $severity === 'notice':
        $this->maxSeverity = Audit::NOTICE;
        break;

      case $severity === Audit::FAIL:
      case $severity === Audit::FAILURE:
      case $severity === Audit::WARNING:
      case $severity === Audit::WARNING_FAIL:
      case $severity === Audit::NOTICE:
        $this->maxSeverity = $severity;
        break;

      default:
        throw new \InvalidArgumentException("Cannot set max severity of policy to: " . var_export($severity, TRUE));
    }
  }

  public function getSeverity($severity) {
    switch (TRUE) {
      // Statuses that we'd never alter.
      case $severity === Audit::PASS:
      case $severity === Audit::SUCCESS:
      case $severity === Audit::NOTICE:
      case $severity === Audit::ERROR:
      case $severity === Audit::NOT_APPLICABLE:
        return $severity;

      case $this->maxSeverity === Audit::NOTICE:
        return Audit::NOTICE;

      case $this->maxSeverity === Audit::WARNING:
      case $severity === Audit::WARNING:
        return Audit::WARNING;

      case $this->maxSeverity === Audit::WARNING_FAIL:
      case $severity === Audit::WARNING_FAIL:
        return Audit::WARNING_FAIL;

      default:
        return $severity;
    }
  }

  /**
   * Retrieve a property value and token replacement.
   *
   * @param $property
   * @param array $replacements
   * @return string
   * @throws \Exception
   */
  public function __construct(array $info) {
    if (isset($info['severity'])) {
      $this->setSeverity($info['severity']);
    }

    parent::__construct($info);
    $this->renderableProperties[] = 'remediation';
    $this->renderableProperties[] = 'success';
    $this->renderableProperties[] = 'failure';
    $this->renderableProperties[] = 'warning';

    $reflect = new \ReflectionClass($this->class);
    $this->remediable = $reflect->implementsInterface('\Drutiny\RemediableInterface');


  }

  /**
   * Validation metadata.
   *
   * @param ClassMetadata $metadata
   */
  public static function loadValidatorMetadata(ClassMetadata $metadata) {
    parent::loadValidatorMetadata($metadata);
    $metadata->addPropertyConstraint('success', new NotBlank());
    $metadata->addPropertyConstraint('failure', new NotBlank());
    $metadata->addPropertyConstraint('parameters', new All(array(
      'constraints' => array(
        new Collection([
          'fields' => [
            'type' => new Optional(new Type("string")),
            'description' => new Optional(new Type("string")),
            'default' => new NotNull(),
          ],
        ]),
      ),
    )));
    $metadata->addPropertyConstraint('tags', new Optional());
  }

  public function getParameterDefaults() {
      $defaults = [];
      foreach ($this->parameters as $name => $info) {
        $defaults[$name] = isset($info['default']) ? $info['default'] : null;
      }
      return $defaults;
  }

}
