<?php

namespace Drutiny;

use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 *
 */
class ProfileInformation {

  protected $title;
  protected $policies = [];
  protected $registry;
  protected $template = 'site';

  /**
   *
   */
  public function __construct(array $info, $ignore_dependencies = FALSE) {
    $this->registry = new Registry();

    foreach ($info as $key => $value) {
      if (!property_exists($this, $key)) {
        continue;
      }
      $this->{$key} = $value;
    }

    // This allows profiles to be built upon one another.
    if (isset($info['include'])) {
      $info['include'] = is_array($info['include']) ? $info['include'] : [$info['include']];
      $profiles = $this->registry->profiles();
      foreach ($info['include'] as $profile) {
        if (!isset($profiles[$profile])) {
          if ($ignore_dependencies) {
            continue;
          }
          throw new \InvalidArgumentException("Profile '$this->title' requires profile '$profile' but doesn't exist");
        }
        $this->policies = array_merge($this->getPolicies(), $profiles[$profile]->getPolicies());
      }
    }

    $chain = new PolicyChain();

    // Ensure each policy exists and add to the policy chain
    // to ensure policy dependencies are added.
    foreach ($this->policies as $check => $args) {
      if (!$this->policyExists($check)) {
        throw new \InvalidArgumentException("Profile '$this->title' specifies check '$check' which does not exist.");
      }
      $chain->add($this->loadPolicy($check));
    }

    // Re-order profile policies based on dependencies
    // running first.
    $policies = [];
    foreach ($chain->getPolicies() as $policy) {
      $name = $policy->get('name');
      $args = isset($this->policies[$name]) ? $this->policies[$name] : [];
      $policies[$name] = $args;
    }
    $this->policies = $policies;

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
  public function getPolicies() {
    return $this->policies;
  }

  protected function policyExists($name)
  {
    $registry = $this->registry->policies();
    return array_key_exists($name, $registry);
  }

  protected function loadPolicy($name)
  {
    $registry = $this->registry->policies();
    return $registry[$name];
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
