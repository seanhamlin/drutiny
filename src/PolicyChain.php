<?php
namespace Drutiny;

/**
 * Provides an ordered list of Policies and dependencies.
 */
class PolicyChain {
  protected $chain = [];

  protected $policies;

  /**
   * Determine if the chain has been built.
   */
  protected $built = FALSE;

  public function __construct()
  {
    $this->policies = (new Registry())->policies();
  }

  /**
   * Add a policy to the policy chain.
   */
  public function add(Policy $policy)
  {
    // Don't add policy if it has already been added.
    if (isset($this->chain[$policy->get('name')])) {
      return TRUE;
    }

    // Since we're adding to the chain, the chain will need to be rebuilt.
    $this->built = FALSE;
    $this->chain[$policy->get('name')] = $policy;

    if ($depends = $policy->get('depends')) {
      foreach ($depends as $name) {
        if (!isset($this->policies[$name])) {
          new \InvalidArgumentException($policy->get('name') . " depends on the '$name' policy but is not available.");
        }
        $this->add($this->policies[$name]);
      }
    }
    return TRUE;
  }

  public function getPolicies()
  {
    if ($this->built) {
      return $this->chain;
    }

    usort($this->chain, function ($a, $b) {
      if (in_array($a->get('name'), $b->get('depends'))) {
        return -1;
      }
      if (in_array($b->get('name'), $a->get('depends'))) {
        return 1;
      }
      return 0;
    });

    $this->built = TRUE;
    return $this->chain;
  }
}

 ?>
