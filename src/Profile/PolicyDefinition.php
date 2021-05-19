<?php

namespace Drutiny\Profile;

use Drutiny\Container;
use Drutiny\Policy;
use Drutiny\PolicySource\UnavailablePolicyException;

class PolicyDefinition {
  use \Drutiny\Policy\ContentSeverityTrait;

  /**
   * Name of the poilcy.
   *
   * @var string
   */
  protected $name;

  /**
   * Weight of the policy in the order of the Profile.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * A list of PolicyDefinition objects that should be ordered before this one.
   *
   * @var array
   */
  protected $positionBefore = [];

  /**
   * Parameters to set on the policy.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * Build a PolicyDefinition from Profile input.
   *
   * @var $name string
   * @var $definition array
   */
  public static function createFromProfile($name, $weight = 0, $definition = [])
  {
    $policyDefinition = new static();
    $policyDefinition->setName($name)
                     ->setWeight($weight);

    if (isset($definition['parameters'])) {
      $policyDefinition->setParameters($definition['parameters']);
    }

    try {
      // Load a policy to get defaults.
      $policy = Policy::load($name);
    }
    catch (UnavailablePolicyException $e) {
      Container::getLogger()->warning($e->getMessage());
      return FALSE;
    }

    if (isset($definition['severity'])) {
      $policyDefinition->setSeverity($definition['severity']);
    }
    else {
      $policyDefinition->setSeverity($policy->getSeverity());
    }

    // Track policies that are depended on.
    // foreach ((array) $policy->get('depends') as $name) {
    //   $policyDefinition->setDependencyPolicyName($name);
    // }

    return $policyDefinition;
  }

  /**
   * Get the name of the policy.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the name of the policy.
   */
  public function setName($name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * Get the weight of the policy.
   */
  public function getWeight()
  {
    return $this->weight;
  }

  /**
   * Set the weight of the policy.
   */
  public function setWeight($weight)
  {
    $this->weight = (int) $weight;
    return $this;
  }

  public function setParameters(Array $params)
  {
    $this->parameters = $params;
  }

  /**
   * Get the policy for the profile.
   */
  public function getPolicy()
  {
    $policy = Policy::load($this->getName());
    if ($this->getSeverity() !== NULL) {
      $policy->setSeverity($this->getSeverity());
    }

    foreach ($this->parameters as $param => $value) {
      $info = ['default' => $value];
      $policy->addParameter($param, $info);
    }
    return $policy;
  }

  /**
   * Track a policy dependency as a policy definition.
   */
  public function setDependencyPolicyName($name)
  {
    $this->positionBefore[$name] = self::createFromProfile($name, $this->getWeight());
    return $this;
  }

  /**
   * Get all dependencies.
   */
  public function getDependencyPolicyDefinitions()
  {
    return $this->positionBefore;
  }

  public function getProfileMetadata()
  {
    return array_filter([
      'parameters' => $this->parameters,
      'severity' => $this->severity
    ]);
  }
}

 ?>
