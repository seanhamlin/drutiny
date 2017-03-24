<?php

// @see http://cgit.drupalcode.org/module_missing_message_fixer/tree/includes/module_missing_message_fixer.drush.inc

$rows = [];

// Grab all the modules in the system table.
$query = db_query("SELECT filename, type, name FROM {system}");
// Go through the query and check to see if the module exists in the directory.
foreach ($query->fetchAll() as $record) {
  // Grab the checker.
  $check = drupal_get_filename($record->type, $record->name, $record->filename, FALSE);
  // If drupal_get_filename returns null = we got problems.
  if (is_null($check) && $record->name != 'default') {
    // Go ahead and set the row if all is well.
    $rows[$record->name] = array(
      'name' => $record->name,
      'type' => $record->type,
      'filename' => $record->filename,
    );
  }
}

print drupal_json_encode($rows);
