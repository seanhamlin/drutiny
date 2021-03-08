<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is enabled check.
 *
 */
class ModuleEnabled extends Audit
{

    public function configure()
    {
           $this->addParameter(
               'module',
               static::PARAMETER_OPTIONAL,
               'The module to check is enabled.',
           );
           $this->setDeprecated();
    }


  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {

        $module = $this->getParameter('module');
        $info = $sandbox->drush(['format' => 'json'])->pmList();

        if (!isset($info[$module])) {
            return false;
        }

        $status = strtolower($info[$module]['status']);

        return ($status == 'enabled');
    }
}
