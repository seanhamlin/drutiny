<?php

namespace Drutiny;

use Drutiny\Config\PolicyConfiguration;
use Drutiny\Policy\Dependency;
use Drutiny\Entity\DataBag;
use Drutiny\Entity\ExportableInterface;
use Drutiny\Policy\UnavailablePolicyException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Policy implements ExportableInterface
{
    const SEVERITY_LOW = 1;
    const SEVERITY_NORMAL = 2;
    const SEVERITY_HIGH = 4;
    const SEVERITY_CRITICAL = 8;

    protected $propertyBag;
    protected $parameterBag;
    protected $remediable = false;
    protected $dependencies = [];
    protected $severityCode;

    public function __construct()
    {
      $this->parameterBag = new DataBag();
      $this->propertyBag = new DataBag();
    }

    /**
     * Make properties read-only attributes of object.
     */
    public function __get(string $property)
    {
      if ($property == 'parameters') {
        return $this->parameterBag->all();
      }
      return $this->propertyBag->get($property);
    }

    /**
     * Required for __get to work in twig templates.
     */
    public function __isset($property)
    {
      if ($property == 'parameters') {
        return true;
      }
      return $this->propertyBag->has($property);
    }

  /**
   * Set policy property.
   */
    public function setProperty(string $property, $value)
    {
        return $this->setProperties([$property => $value]);
    }

    public function setProperties(array $new_properties = [])
    {

        $data = $this->propertyBag->all();

        foreach ($new_properties as $property => $value) {
            $data[$property] = $value;
        }

        $processor = new Processor();
        $configuration = new PolicyConfiguration();

        try {
          $policyData = $processor->processConfiguration(
              $configuration,
              ['policy' => $data]
          );
        }
        catch (InvalidConfigurationException $e) {
            throw new InvalidConfigurationException("Policy '{$data['name']}' configuration invalid: " . $e->getMessage());
        }


        // Parameters sit on their own DataBag.
        if (isset($new_properties['parameters'])) {
            $this->parameterBag->add($new_properties['parameters']);
            unset($new_properties['parameters']);
        }

        $this->propertyBag->add($policyData);

        if (isset($new_properties['class'])) {
            try {
              $reflect = new \ReflectionClass($policyData['class']);
            }
            catch (\ReflectionException $e) {
              throw new UnavailablePolicyException("Policy {$data['name']} is not available. Class {$policyData['class']} does not exist.");
            }
        }

        if (isset($new_properties['depends'])) {
            $builder = function ($depends) {
                return new Dependency($depends['expression'], $depends['on_fail'], $depends['syntax'], $depends['description']);
            };
            $this->dependencies = array_map($builder, $policyData['depends']);
        }

        if (!isset($this->severityCode)) {
          $this->severityCode = Policy::SEVERITY_NORMAL;
        }

      // Map a severity value to its respective security code.
        if (isset($new_properties['severity'])) {
            switch ($policyData['severity']) {
                case 'low':
                    $this->severityCode = Policy::SEVERITY_LOW;
                    break;
                case 'normal':
                    $this->severityCode = Policy::SEVERITY_NORMAL;
                    break;
                case 'high':
                    $this->severityCode = Policy::SEVERITY_HIGH;
                    break;
                case 'critical':
                    $this->severityCode = Policy::SEVERITY_CRITICAL;
                    break;
            }
        }

        return $this;
    }

    public function addParameter(string $key, $value)
    {
        return $this->parameterBag->set($key, $value);
    }

    public function addParameters(array $parameters)
    {
      return $this->parameterBag->add($parameters);
    }

    public function getParameter(string $key)
    {
      return $this->parameterBag->get($key);
    }

    public function getAllParameters()
    {
      return $this->parameterBag->all();
    }

  /**
   * Get list of Drutiny\Policy\Dependency objects.
   */
    public function getDepends()
    {
        return $this->dependencies;
    }

    public function setSeverity(string $severity)
    {
        return $this->setProperty('severity', $severity);
    }

    public function getSeverity()
    {
        return $this->severityCode;
    }

    public function export()
    {
        $data = $this->propertyBag->all();
        $data['parameters'] = $this->parameterBag->all();
        return $data;
    }
}
