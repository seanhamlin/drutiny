<?php

namespace Drutiny\Policy;

use Drutiny\Sandbox\Sandbox;
use Drutiny\ExpressionLanguage;
use Drutiny\Audit\AuditInterface;
use Drutiny\Container;

class Dependency
{

  /**
   * On fail behaviour: Fail policy in report.
   */
    const ON_FAIL_DEFAULT = 'fail';

  /**
   * On fail behaviour: Omit policy from report.
   */
    const ON_FAIL_OMIT = 'omit';

  /**
   * On fail behaviour: Report policy as error.
   */
    const ON_FAIL_ERROR = 'error';

  /**
   * On fail behaviour: Report as not applicable.
   */
    const ON_FAIL_REPORT_ONLY = 'report_only';

  /**
   * @var string Must be one of ON_FAIL constants.
   */
    protected string $onFail = 'fail';

  /**
   * @var string Symfony ExpressionLanguage expression.
   */
    protected string $expression;

    /**
     * @var string Evaluation syntax.
     */
    protected string $syntax;

    /**
     * @var string A description of what the dependency is about.
     */
    protected string $description;

    public function __construct(
      $expression = 'true',
      $on_fail = self::ON_FAIL_DEFAULT,
      $syntax = 'expression_language',
      $description = ''
      )
    {
        $this->expression = $expression;
        $this->setFailBehaviour($on_fail);
        $this->syntax = $syntax;
        $this->description = $description;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function getFailBehaviour()
    {
        switch ($this->onFail) {
            case self::ON_FAIL_ERROR:
                return AuditInterface::ERROR;

            case self::ON_FAIL_REPORT_ONLY:
                return AuditInterface::NOT_APPLICABLE;

            case self::ON_FAIL_OMIT:
                return AuditInterface::IRRELEVANT;

            case self::ON_FAIL_DEFAULT;
            default:
            return AuditInterface::FAIL;
        }
    }

    public function export()
    {
        return [
        'on_fail' => $this->onFail,
        'expression' => $this->expression,
        ];
    }

    public function setFailBehaviour($on_fail = self::ON_FAIL_DEFAULT)
    {
        switch ($on_fail) {
            case self::ON_FAIL_ERROR:
            case self::ON_FAIL_DEFAULT:
            case self::ON_FAIL_REPORT_ONLY:
            case self::ON_FAIL_OMIT:
                $this->onFail = $on_fail;
                return $this;
            default:
                throw new \Exception("Unknown behaviour: $on_fail.");
        }
    }

    /**
     * Get the description.
     */
    public function getDescription():string
    {
      return $this->description;
    }

  /**
   * Evaluate the dependency.
   */
    public function execute(AuditInterface $audit)
    {
        try {
          $return = $audit->evaluate($this->expression, $this->syntax, [
            'dependency' => $this
          ]);
          switch (true) {
              case $return === AuditInterface::PASS:
              case $return === AuditInterface::SUCCESS:
                return true;
          }
        } catch (\Exception $e) {
            $audit->getLogger()->warning($this->syntax . ': ' . $e->getMessage());
        }
        $audit->getLogger()->debug(__CLASS__ . ": Expression FAILED. ");

        // Execute the on fail behaviour.
        throw new DependencyException($this);
    }
}
