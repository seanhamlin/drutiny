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

/**
 *
 */
class Policy {

  protected $title;
  protected $name;
  protected $class;
  protected $description;
  protected $remediation;
  protected $success;
  protected $failure;
  protected $warning;
  protected $parameters = [];
  protected $remediable = FALSE;
  protected $validation = [];
  protected $tags = [];
  protected $depends = [];
  protected $maxSeverity;
  protected $filepath;

  protected $renderableProperties = [
    'title',
    'name',
    'description',
    'remediation',
    'success',
    'failure',
    'warning'
  ];

  /**
   *
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

    if (count($errors) > 0) {
      /*
       * Uses a __toString method on the $errors variable which is a
       * ConstraintViolationList object. This gives us a nice string
       * for debugging.
       */
      $errorsString = (string) $errors;
      throw new \InvalidArgumentException($errorsString . PHP_EOL . print_r($info, 1));
    }

    $reflect = new \ReflectionClass($this->class);
    $this->remediable = $reflect->implementsInterface('\Drutiny\RemediableInterface');
  }

  /**
   * Render a property.
   */
  protected function render($markdown, $replacements) {
    $m = new \Mustache_Engine();
    return $m->render($markdown, $replacements);
  }

  /**
   * Set the max severity of an audited outcome applicable to this policy.
   *
   * This ensures that an audit doesn't set a severity that doesn't match the
   * importance of the policy. This does not apply for Audit::ERROR or
   * Audit::NOT_APPLICABLE responses.
   */
  public function setMaxSeverity($severity = Audit::FAIL)
  {
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

  public function getSeverity($severity)
  {
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
   */
  public function get($property, $replacements = []) {
    if (!isset($this->{$property})) {
      throw new \Exception("Attempt to retrieve unknown property: $property. Available properties: \n" . print_r((array) $this, 1));
    }
    if (in_array($property, $this->renderableProperties)) {
      return $this->render($this->{$property}, $replacements);
    }
    return $this->{$property};
  }

  public function has($property) {
    return isset($this->{$property});
  }

  public function hasTag($tag) {
    return in_array($tag, $this->tags);
  }

  public function getTags() {
    return $this->tags;
  }

  /**
   * Validation metadata.
   */
  public static function loadValidatorMetadata(ClassMetadata $metadata) {
    $metadata->addPropertyConstraint('title', new Type("string"));
    $metadata->addPropertyConstraint('name', new Type("string"));
    $metadata->addPropertyConstraint('class', new Callback(function ($class, ExecutionContextInterface $context, $payload) {
      if (!class_exists($class)) {
        $context->buildViolation("$class is not a valid class.")
          ->atPath('class')
          ->addViolation();
      }
    }));
    $metadata->addPropertyConstraint('description', new NotBlank());
    $metadata->addPropertyConstraint('remediation', new Optional());
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

  public function getParameterDefaults()
  {
      $defaults = [];
      foreach ($this->parameters as $name => $info) {
        $defaults[$name] = isset($info['default']) ? $info['default'] : null;
      }
      return $defaults;
  }

}
