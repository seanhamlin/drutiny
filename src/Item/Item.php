<?php

namespace Drutiny\Item;

use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Report on collected data.
 */
class Item {
  const SEVERITY_NONE = 0;
  const SEVERITY_LOW = 1;
  const SEVERITY_NORMAL = 2;
  const SEVERITY_HIGH = 4;
  const SEVERITY_CRITICAL = 8;

  /**
   * @string Title of the policy. A human readable value.
   */
  protected $title;

  /**
   * @string Name of the policy. A machine readable value.
   */
  protected $name;

  /**
   * @string Reference to a \Drutiny\AuditInterface class.
   */
  protected $class;

  /**
   * @string A description of what a policy should audit.
   */
  protected $description;

  /**
   * @string A description of what a policy should audit.
   */
  protected $type = 'audit';

  /**
   * @array tokens to to replace in tokenized attributes when rendered.
   */
  protected $tokens = [];

  /**
   * @array A convention for categorising a policy.
   */
  protected $tags = [];

  /**
   * @array list of object attributes that may be passed through the renderer.
   */
  protected $renderableProperties = [
    'title',
    'name',
    'description'
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
  }

  /**
   * Render a property.
   */
  protected function render($markdown, $replacements) {
    $m = new \Mustache_Engine();
    try {
      return $m->render($markdown, $replacements);
    }
    catch (\Mustache_Exception $e) {
      throw new \Exception("Error in $this->name: " . $e->getMessage());
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
    $metadata->addPropertyConstraint(
      'type',
       new Choice(array('audit', 'data'))
    );
    $metadata->addPropertyConstraint('class', new Callback(function ($class, ExecutionContextInterface $context, $payload) {
      try {
        if (!class_exists($class)) {
          throw new \Exception("$class does not exist.");
        }
          $reflect = new \ReflectionClass($class);
        if (!$reflect->isSubclassOf('\Drutiny\Audit')) {
          throw new \Exception("$class does not extend \Drutiny\Audit.");
        }
      }
      catch (\Exception $e) {
        $context->buildViolation("$class is not a valid class: " . $e->getMessage())
          ->atPath('class')
          ->addViolation();
      }
    }));
    $metadata->addPropertyConstraint('description', new NotBlank());
  }
}
