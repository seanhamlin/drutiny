<?php

/**
 * @file
 * Contains ${NAMESPACE}\TextFormatCheckBase
 */
namespace Drutiny\Check\Security;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Check\Check;
use Drutiny\Helpers\Text;


abstract class TextFormatBase extends Check {

  /**
   * @return array
   */
  abstract protected function getFormats();

  /**
   * @return array
   */
   private function getDefaultFilters() {
     return [
       'filter_html' => ['unathenticated'],
     ];
   }

  /**
   * Perform a check for filters on exposed text formats.
   *
   * @return int
   *  An audit response.
   */
  final public function check() {
    $errors = [];
    $check_filters = $this->getOption('text_formats', $this->getDefaultFilters());
    $formats = $this->getFormats();

    foreach ($formats as $format => $info) {
      foreach ($check_filters as $filter => $roles) {
        // Check to see if the filter is available and more roles than those
        // allowed have access to the filter.
        $intersect = array_intersect(array_keys($info->roles), $roles);

        if (empty($intersect) || empty($info->filters->{$filter})) {
          // If there is no difference in the roles then we don't have any
          // unexpected users who have access to this filter format.
          continue;
        }

        $errors[] = Text::translate('<strong>:format_name [:format_id]</strong> filters by <strong>:filter</strong> and is allowed by <code>:roles</code>.', [
          ':format_name' => $info->name,
          ':format_id' => $format,
          ':filter' => $filter,
          ':roles' => implode(', ', $intersect),
        ]);
      }
    }

    $this->setToken('num', count($formats));
    $this->setToken('errors', implode('</li><li>', $errors));

    if (!empty($errors)) {
      return AuditResponse::AUDIT_WARNING;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
