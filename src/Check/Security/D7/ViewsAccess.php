<?php
/**
 * @file
 * Contains Drutiny\Check\Security\D7\ViewsAccess
 */

namespace Drutiny\Check\Security\D7;


use Drutiny\Check\Security\ViewsAccessBase;
use Drutiny\Annotation\CheckInfo;
use Drutiny\Helpers\Serializer;

/**
 * @CheckInfo(
 *   title = "Views Access",
 *   description = "Ensure views meet minimum access permissions.",
 *   remediation = "Update views to match expected permissions.",
 *   success = "<code>:num</code> views configured correctly.",
 *   failure = "<code>:no_access_num</code> view:no_access_plural have no access control set<br/><ul><li>:no_access</li></ul><br/><br/><code>:weak_num</code> view:weak_num_plural have weak permissions<br/><ul><li>:weak</li></ul>",
 *   exception = "Could not find any views",
 *   not_available = "No views available",
 * )
 */
class ViewsAccess extends ViewsAccessBase {

  /**
   * Access all views for the site.
   *
   * @return array
   *   All available views.
   */
  protected function getViews() {

    // We assume that permissions will not be set on other types of views and
    // their permissions will be controlled via the embed method (blocks, panes
    // etc.).
    $sql = "SELECT vv.vid, vv.name, vv.description, vv.human_name, vd.id, vd.display_title, vd.display_options, vd.display_plugin
    FROM {views_view} vv
    LEFT JOIN {views_display} vd
    ON vd.vid = vv.vid
    WHERE vd.display_plugin = 'page'";

    $results = $this->context->drush->sqlQuery($sql);
    $views = [];

    foreach ($results as $row) {
      list($vid, $name, $description, $human_name, $display_id, $display_title, $display_options, $display_plugin) = explode("\t", $row);

      // Build a loose object that looks similar to views_get_all_views().
      $view = isset($views[$name]) ? $views[$name] : (object) [
        'vid' => $vid,
        'machine_name' => $name,
        'name' => $human_name,
        'display' => [],
      ];

      $view->display[$display_id] = (object) [
        'display_id' => $display_id,
        'title' => $display_title,
        'display_plugin' => $display_plugin,
        'display_options' => Serializer::unserialize($display_options),
      ];

      $views[$vid] = $view;
    }

    return $views;
  }
}
