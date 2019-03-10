<?php

namespace Drutiny\Target;

use Drutiny\Annotation\Metadata;

interface DrushTargetInterface extends TargetInterface {
  /**
   * Return an array of Drush options from the Target site-alias.
   */
  public function getOptions();

  /**
   * Return the Drush site-alias.
   */
  public function getAlias();

  /**
   * Drush status data.
   * @Metadata(name = "drush.status")
   */
  public function metadataDrushStatus();

  /**
   * Get the PHP version in use.
   * @Metadata(name = "php_version")
   */
  public function metadataPhpVersion();

  /**
   * Get the active theme path of a Drupal site.
   * @Metadata(name = "theme_path")
   */
  public function metadataThemePath();
}
