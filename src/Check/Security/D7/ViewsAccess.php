<?php
/**
 * @file
 * Contains Drutiny\Check\Security\D7\ViewsAccess
 */

namespace Drutiny\Check\Security\D7;

use Drutiny\Check\Security\ViewsAccessBase;
use Drutiny\Annotation\CheckInfo;

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
    /** @var \Drutiny\Base\DrushCaller $drush */
    $drush = $this->context->drush;

    $views = $drush->runScript('ViewsGetAllViews');

    if (empty($views)) {
      return [];
    }

    foreach ($views as $name => &$view) {
      $view = json_decode($view);
    }

    return $views;
  }
}