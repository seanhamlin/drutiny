<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;


class PhpExtensionAnalysis extends AbstractAnalysis
{

  public function configure()
  {
    $this->addParameter(
        'expression',
        static::PARAMETER_REQUIRED,
        'The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html'
      )
      ->addParameter(
        'warning',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      )
      ->addParameter(
        'variables',
        static::PARAMETER_OPTIONAL,
        'A keyed array of expressions to set variables before evaluating the passing expression.',
        []
      )
      ->addParameter(
        'syntax',
        static::PARAMETER_OPTIONAL,
        'expression_language or twig',
        'expression_language'
      )
      ->addParameter(
        'not_applicable',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      );
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {
    parent::gather($sandbox);

    $phpext = $this->target->getService('drush')->runtime(function () {
        return get_loaded_extensions();
    });
// print_r($phpext);
//     $extensions = [];
//     foreach ( $phpext as $name => $values ) {
//       $extensions[] = [
//         'name' => $name,
//         // 'global_value' => $values['global_value'],
//         // 'local_value' => $values['local_value'],
//         // 'access' => $values['access']
//       ];
//     }

    $this->set('phpextensions', $phpext);

  }
}
