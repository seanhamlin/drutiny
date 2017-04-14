<?php

namespace Drutiny;

use Assert\Type;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 *
 */
class ProfileInformation {

  protected $title;
  protected $checks;

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
      $errorsString = (string) $errors;
      throw new \InvalidArgumentException($errorsString);
    }
  }

  /**
   * Retrieve a property value and token replacement.
   */
  public function get($property, $replacements = []) {
    if (!isset($this->{$property})) {
      throw new \Exception("Attempt to retrieve unknown property: $property.");
    }

    if (isset($this->renderableProperties[$property])) {
      return $this->render($this->{$property}, $replacements);
    }
    return $this->{$property};
  }

  /**
   *
   */
  public function getChecks() {
    return $this->checks;
  }

  /**
   * Validation metadata.
   */
  public static function loadValidatorMetadata(ClassMetadata $metadata) {
    // $checks = Registry::checks();
    $metadata->addPropertyConstraint('title', new Type("string"));

    // TODO: Validate checks in profile.
    // $metadata->addPropertyConstraint('checks', new Assert\All([
    //   'constraints' => [
    //     new Assert\Callback(function ($name, ExecutionContextInterface $context, $payload) use ($checks) {
    //         if (!isset($checks[$name])) {
    //             $context->buildViolation("$name is not a valid check.")
    //                 ->atPath('checks')
    //                 ->addViolation();
    //         }
    //     }
    //   ]
    // ]);
  }

}
