<?php
/**
 * @file
 * Load all views with the API so we don't miss anything.
 */

$output = [];
foreach (views_get_all_views() as $name => $view) {
  // Serializing can't be used here unless we defined \views object.
  $output[$name] = json_encode($view);
}
print json_encode($output);