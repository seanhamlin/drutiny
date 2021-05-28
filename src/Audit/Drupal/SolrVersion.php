<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Check the version of Drupal project in a site.
 *
 *
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

        echo 'searching: ' . $version_searched . PHP_EOL;

        $info = $sandbox->drush(['format' => 'json'])->pmList();
        $module_version = $info[$module]['version'];

        // Do we have version 3 or 4?
        preg_match('/^2.*.*/', $module_version, $solr34_found);
        // Do we have version 7?
        preg_match('/^3.*.*/', $module_version, $solr7_found);

        $solr_found = FALSE;
        if (!empty($solr34_found)) {
            echo '2.x version found of acquia search module. SOLR version in use: 3 or 4';
            if ($version_searched == '3' || $version_searched == '4') {
                $solr_found = TRUE;
            }
        } elseif (!empty($solr7_found)) {
            echo '3.x version found of acquia search module. SOLR version: 7';
            if ($version_searched = '7') {
                $solr_found = TRUE;
            }
        }

        $sandbox->logger()->info("($version_searched, $solr_found)");

        return $solr_found;
    }
}
