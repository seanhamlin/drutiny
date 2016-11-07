$views = [];
foreach (views_get_all_views() as $name => $view) {
  $views[] = $name;
}

print drupal_json_encode(array(
  'views' => $views,
));
