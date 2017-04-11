<?php

$output = [];
foreach (views_get_all_views() as $name => $view) {
  // Serializing can't be used here unless we define the \views object.
  $output[$name] = json_encode($view);
}
print json_encode($output);