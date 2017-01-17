<?php
/**
 * @file
 * Contains SiteAudit\Check\D7\EntityReferenceAutocomplete
 */

namespace SiteAudit\Check\D7;

use SiteAudit\Check\Check;
use SiteAudit\AuditResponse\AuditResponse;
use SiteAudit\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "Entity reference autocomplete",
 *  description = "Ensure that entity reference fields are configured correctly.",
 *  remediation = "Change the following field definitions to autocomplete - <br/>:error_replace.",
 *  success = "Found <code>:num_fields</code> entity reference field:num_plural configured correctly.",
 *  failure = "Found configuration errors in <code>:error_count</code> entity reference field:error_plural.",
 *  exception = "Could not find any entity reference fields.",
 *  not_available = "No entity reference fields.",
 * )
 */
class EntityReferenceAutocomplete extends Check {

  protected $errors = [];

  /**
   * Identify if entity reference fields are displaying select lists.
   *
   * Sites can see crippling performance if an entity reference field is being
   * used to display entities from a large node pool. This process will never
   * fail with max_execution_time and as a result can cause PHP-FPM to backup
   * and queue requests while it deals with an errant entity reference field.
   *
   * @return int AuditResponse::AUDIT_SUCCESS or similar.
   */
  protected function check() {
    $valid = 0;

    try {
      $output = $this->context->drush->sqlQuery('SELECT fc.field_name, fc.data, fci.data FROM {field_config} fc JOIN {field_config_instance} fci ON fc.id = fci.field_id WHERE fc.type = \'entityreference\'');
    } catch (\Exception $e) {
      return AuditResponse::AUDIT_FAILURE;
    }

    foreach ($output as $line) {
      list($field_name, $field_info, $field_instance_info) = explode("\t", $line);
      $field_info = unserialize($field_info);
      $field_instance_info = unserialize($field_instance_info);

      if (strpos($field_instance_info['widget']['type'], 'autocomplete') > -1) {
        // Correct configuration is to use an autocomplete as this will limit
        // the number of referenced entities from being rendered.
        $valid++;
        continue;
      }

      $check = "check_{$field_info['settings']['target_type']}";

      if (method_exists($this, $check)) {
        if (call_user_func([$this, $check], $field_info, $field_instance_info)) {
          $valid++;
        }
      }
    }


    $this->setToken('num_fields', $valid);
    $this->setToken('num_plural', $valid > 1 ? 's' : '');
    $this->setToken('error_replace', implode(', ',  $this->errors));
    $this->setToken('error_count', count($this->errors));
    $this->setToken('error_plural', count($this->errors) > 1 ? 's' : '');

    if (!empty($this->errors)) {
      return AuditResponse::AUDIT_FAILURE;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }

  /**
   * Attempt to extract field arguments from the settings arrays.
   *
   * @param array $field_info
   *   A field info array.
   *
   * @return array
   */
  private function getFieldArgs(array $field_info) {
    $args = [];

    $handler_settings = isset($field_info['settings']['handler_settings']) ? $field_info['settings']['handler_settings'] : NULL;

    if (empty($handler_settings)) {
      return $args;
    }

    // Attempt to find the node types in the $field_info.
    if (isset($handler_settings['target_bundles'])) {
      $args = array_keys($handler_settings['target_bundles']);
    }
    elseif (isset($handler_settings['view']['args'])) {
      $args = $handler_settings['view']['args'];
    }

    return $args;
  }

  /**
   * Check method for an entity reference field referencing terms.
   *
   * @param array $field_info
   *   A field info array.
   * @param $field_instance_info
   *   A particular field instance info array.
   *
   * @return bool|null
   */
  private function check_taxonomy_term($field_info, $field_instance_info) {
    $bundles = $this->getFieldArgs($field_info);

    if (empty($bundles)) {
      return $this->getOption('implicit', TRUE);
    }

    try {
      $output = $this->context->drush->sqlQuery('SELECT count(ttd.tid) as count FROM {taxonomy_term_data} ttd');
    } catch(\Exception $e) {
      return $this->getOption('implicit', TRUE);
    }

    list($count) = explode("\t", reset($output));

    if ($count > $this->getOption('threshold', 100)) {
      $this->errors[$field_info['id']] = "{$field_instance_info['label']} ({$field_info['id']})";
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check method for an entity reference field referencing nodes.
   *
   * @param array $field_info
   *   A field info array.
   * @param $field_instance_info
   *   A particular field instance info array.
   *
   * @return bool|null
   */
  private function check_node(array $field_info, array $field_instance_info) {
    $node_types = $this->getFieldArgs($field_info);

    if (empty($node_types)) {
      return $this->getOption('implicit', TRUE);
    }

    $node_types = "'" . implode("','", $node_types) . "'";

    try {
      $output = $this->context->drush->sqlQuery('SELECT count(node.nid) as count FROM {node} node WHERE node.type in (' . $node_types . ')');
    } catch (\Exception $e) {
      return $this->getOption('implicit', TRUE);
    }

    list($count) = explode("\t", reset($output));

    if ($count > $this->getOption('threshold', 100)) {
      $this->errors[$field_info['id']] = "{$field_instance_info['label']} ({$field_info['id']})";
      return FALSE;
    }

    return TRUE;
  }
}
