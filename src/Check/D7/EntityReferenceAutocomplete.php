<?php

namespace Drutiny\Check\D7;

use Drutiny\Base\Serializer;
use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Entity reference autocomplete",
 *  description = "Ensure that entity reference fields are configured correctly.",
 *  remediation = "Change the following field definitions to autocomplete as they are referencing entities above the threshold (<b>:threshold</b>): <ul><li>:error_replace.</li></ul>",
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

    // We don't need to perform these checks if the entity reference module is
    // not enabled.
    if (!$this->context->drush->moduleEnabled('entityreference')) {
      return AuditResponse::AUDIT_NA;
    }

    try {
      $output = $this->context->drush->sqlQuery("SELECT fc.field_name, fc.data, fci.data FROM {field_config} fc JOIN {field_config_instance} fci ON fc.id = fci.field_id WHERE fc.type = 'entityreference'");
    }
    catch (\Exception $e) {
      throw new DoesNotApplyException();
    }

    foreach ($output as $line) {
      list($field_name, $field_info, $field_instance_info) = explode("\t", $line);

      $field_info = Serializer::unserialize($field_info);
      $field_instance_info = Serializer::unserialize($field_instance_info);

      // It is possible for the unserialize to fail- this is usually due to
      // ", ', :, or ; characters appearing in the serialized string and not
      // being escaped correctly when retrieved from MySQL.
      if (!$field_info || !$field_instance_info) {
        // Skip this field - possibly want to add the field to the error results
        // with a message about unable to determine configuration.
        continue;
      }

      if (strpos($field_instance_info['widget']['type'], 'autocomplete') > -1) {
        // Correct configuration is to use an autocomplete as this will limit
        // the number of referenced entities from being rendered.
        $valid++;
        continue;
      }

      // Additional checks can be added here; we've defined taxonomy_term and
      // node checks as these are the typical entity reference providers.
      $check = "get_results_{$field_info['settings']['target_type']}";

      if (method_exists($this, $check)) {
        $output = call_user_func([$this, $check], $field_name, $field_info, $field_instance_info);
        if ($output === TRUE || $this->processResult($output, $field_name, $field_info, $field_instance_info)) {
          $valid++;
        }
      }
    }

    $this->setToken('num_fields', $valid);
    $this->setToken('num_plural', $valid > 1 ? 's' : '');
    $this->setToken('error_replace', implode('</li><li>', $this->errors));
    $this->setToken('error_count', count($this->errors));
    $this->setToken('error_plural', count($this->errors) > 1 ? 's' : '');
    $this->setToken('threshold', $this->getOption('threshold', 100));

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
      $args = [];
      foreach ($handler_settings['view']['args'] as $arg) {
        // If we're using views we can pass multiple entity types in
        // contextually separated by a +.
        $args = array_merge($args, explode('+', $arg));
      }
    }

    return $args;
  }

  /**
   * Process the result of a query to determine the entity reference imapact.
   *
   * This is called after a specific get_result_[type] method has been called
   * it will take the output of the query and compare it with the threshold
   * and add a message to errors if required.
   *
   * @param array $output
   *   An output from a query against the configured entity bundle.
   * @param string $field_name
   *   Field machine name.
   * @param array $field_info
   *   Field info array as extracted from the DB.
   * @param array $field_instance_info
   *   Field instance info array as extracted from the DB.
   *
   * @return bool
   */
  private function processResult(array $result = [], $field_name = '', array $field_info = [], array $field_instance_info = []) {
    $field_id = isset($field_info['label']) ? $field_info['label'] : $field_name;
    $field_instance_label = isset($field_instance_info['label']) ? $field_instance_info['label'] : 'Undefined';

    list($count) = explode("\t", reset($result));

    if ($count > $this->getOption('threshold', 100)) {
      $this->errors[$field_id] = "{$field_instance_label} <code>{$field_id}</code>, found <em>{$count}</em> referenced entities";
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check method for an entity reference field referencing terms.
   *
   * @param array $field_info
   *   A field info array.
   * @param $field_instance_info
   *   A particular field instance info array.
   *
   * @return bool|array
   *   The result of the drush query.
   */
  private function get_results_taxonomy_term($field_name, array $field_info, array $field_instance_info) {
    $bundles = $this->getFieldArgs($field_info);

    if (empty($bundles)) {
      return $this->getOption('implicit', TRUE);
    }

    try {
      $result = $this->context->drush->sqlQuery("SELECT count(ttd.tid) as count FROM {taxonomy_term_data} ttd");
    }
    catch (\Exception $e) {
      return $this->getOption('implicit', TRUE);
    }

    return $result;
  }

  /**
   * Check method for an entity reference field referencing nodes.
   *
   * @param array $field_info
   *   A field info array.
   * @param $field_instance_info
   *   A particular field instance info array.
   *
   * @return bool|array
   *   The result from a drush query.
   */
  private function get_results_node($field_name, array $field_info, array $field_instance_info) {
    $node_types = $this->getFieldArgs($field_info);

    if (empty($node_types)) {
      return $this->getOption('implicit', TRUE);
    }

    $node_types = "'" . implode("','", $node_types) . "'";

    try {
      $result = $this->context->drush->sqlQuery("SELECT count(node.nid) as count FROM {node} node WHERE node.type in (" . $node_types . ")");
    }
    catch (\Exception $e) {
      return $this->getOption('implicit', TRUE);
    }

    return $result;
  }

}
