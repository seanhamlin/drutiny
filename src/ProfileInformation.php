<?php

namespace Drutiny;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 *
 */
class ProfileInformation {

  protected $title;
  protected $policies = [];
  protected $registry;
  protected $template = 'page';
  protected $description = '';
  protected $content;

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

    if (empty($this->content)) {
      $this->content = Yaml::parse(file_get_contents(dirname(__FILE__) . '/../Profiles/content.default.yml'));
    }

    // This allows profiles to be built upon one another.
    if (isset($info['include']) && !$ignore_dependencies) {
      $info['include'] = is_array($info['include']) ? $info['include'] : [$info['include']];
      $profiles = $this->registry->profiles();
      foreach ($info['include'] as $profile) {
        if (!isset($profiles[$profile])) {
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
    $metadata->addPropertyConstraint('title', new Assert\Type("string"));
    $metadata->addPropertyConstraint('content', new Assert\Type("array"));
    $metadata->addPropertyConstraint('content', new Assert\Callback(function ($array, ExecutionContextInterface $context) {
      foreach ($array as $idx => $section) {
        if (!isset($section['heading'])) {
          $context->buildViolation('Missing property "heading"')
               ->atPath("content[$idx].heading")
               ->addViolation();
        }
        if (!isset($section['body'])) {
          $context->buildViolation('Missing property "body"')
               ->atPath("content[$idx].body")
               ->addViolation();
        }
      }
    }));
  }

}
