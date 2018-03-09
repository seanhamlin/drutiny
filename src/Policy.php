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
use Symfony\Component\Console\Output\ConsoleOutput;
use Drutiny\Item\Item;
use Drutiny\Logger\ConsoleLogger;



/**
 *
 */
class Policy extends Item {
  use \Drutiny\Item\ContentSeverityTrait;
  use \Drutiny\Item\ParameterizedContentTrait {
    getParameterDefaults as public useTraitgetParameterDefaults;
  }

  /**
   * @string A written recommendation of what remediation to take if the policy fails.
   */
  protected $remediation;

  /**
   * @string A written failure message template. May contain tokens.
   */
  protected $failure;

  /**
   * @string A written success message. May contain tokens.
   */
  protected $success;

  /**
   * @string A written warning message. May contain tokens.
   */
  protected $warning;

  /**
   * @string An array of dependencies.
   */
  protected $depends = [];

  /**
   * @boolean Determine if a policy is remediable.
   */
  protected $remediable;

  /**
   * @string Absolute location of the YAML policy file.
   */
  protected $filepath;

  /**
   * Retrieve a property value and token replacement.
   *
   * @param $property
   * @param array $replacements
   * @return string
   * @throws \Exception
   */
  public function __construct(array $info) {

    $severity = isset($info['severity']) ? $info['severity'] : self::SEVERITY_NORMAL;

    // Data type policies do not have a severity.
    if ($this->type == 'data') {
      $severity = self::SEVERITY_NONE;
    }
    $this->setSeverity($severity);

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

  /**
   * Override ParameterizedContentTrait::getParameterDefaults.
   */
  public function getParameterDefaults()
  {
      $defaults = $this->useTraitgetParameterDefaults();

      $audit = (new Registry)->getAuditMedtadata($this->class);

      // Validation. Look for parameters specificed by the policy and not the
      // audit.
      foreach (array_keys($defaults) as $name) {
        if (!isset($audit->params[$name])) {
          (new ConsoleLogger(new ConsoleOutput()))->warning(strtr('Policy :name documents parameter ":param" not documented by :class.', [
            ':name' => $this->name,
            ':param' => $name,
            ':class' => $this->class,
          ]));
        }
      }

      foreach ($audit->params as $param) {
        if (!isset($defaults[$param->name])) {
          $defaults[$param->name] = isset($param->default) ? $param->default : null;
        }
      }

      return $defaults;
  }
}
