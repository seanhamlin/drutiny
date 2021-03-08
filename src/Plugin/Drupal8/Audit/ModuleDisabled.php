<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is disabled check.
 */
class ModuleDisabled extends Audit
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

        try {
            $info = $sandbox->drush(['format' => 'json'])->pmList();
        } catch (\Exception $e) {
            return strpos($e->getProcess()->getOutput(), $module . ' was not found.') !== false;
        }

        if (!isset($info[$module])) {
            return true;
        }

        $status = strtolower($info[$module]['status']);

        return ($status != 'enabled');
    }
}
