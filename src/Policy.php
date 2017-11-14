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
  protected $parameters = [];
  protected $remediable = FALSE;
  protected $validation = [];
  protected $tags = [];
  protected $depends = [];

  protected $renderableProperties = [
    'title',
    'name',
    'description',
    'remediation',
    'success',
    'failure'
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
    $this->remediable = $reflect->implementsInterface('\Drutiny\Check\RemediableInterface');
  }

  /**
   * Render a property.
   */
  protected function render($markdown, $replacements) {
    $m = new \Mustache_Engine();
    return $m->render($markdown, $replacements);
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
