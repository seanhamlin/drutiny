<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Check the version of Drupal project in a site.
 * @Param(
 *  name = "module",
 *  description = "The module to version information for",
 *  type = "string"
 * )
 * @Param(
 *  name = "version",
 *  description = "The static version to check against.",
 *  type = "string"
 * )
 * )
 */
class SolrVersion extends Audit
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->addParameter(
            'module',
            static::PARAMETER_OPTIONAL,
            'Module to check.'
        );
        $this->addParameter(
            'version',
            static::PARAMETER_OPTIONAL,
            'Module to check.'
        );
    }

    public function audit(Sandbox $sandbox)
    {
        $module = $sandbox->getParameter('module');
        // Version of SOLR we need to find if exists.
        $version_searched = $sandbox->getParameter('version');


        $info = $sandbox->drush(['format' => 'json'])->pmList();
        $module_version = $info[$module]['version'];

        // Do we have version 3 or 4?
        preg_match('/^2.*.*/', $module_version, $solr34_found);
        // Do we have version 7?
        preg_match('/^3.*.*/', $module_version, $solr7_found);

        $solr_found = FALSE;
        if (!empty($solr34_found)) {
            if ($version_searched == '3' || $version_searched == '4') {
                $solr_found = TRUE;
            }
        } elseif (!empty($solr7_found)) {
            if ($version_searched = '7') {
                $solr_found = TRUE;
            }
        }

        $sandbox->logger()->info("($version_searched, $solr_found)");
        if ($solr_found) {
            if ($info[$module]['status'] == 'Enabled') {
                return Audit::FAIL;
            } else {
                // If not enabled we'll assume Disabled, even for empty or different in status field.
                return Audit::WARNING;
            }
        }
        // No acquia_search found, which means customer is not using SOLR.
        return Audit::PASS;

    }
}
