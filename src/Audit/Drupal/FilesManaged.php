<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Anonymous sessions
 */
class FilesManaged extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();
    $root = $stat['root'];
    $files = $stat['files'];
 
    $filesManaged = explode("\n",$sandbox->drush()->sqlq("SELECT uri FROM file_managed"));
    $command = "find @location -type f -follow -print | grep -v -E '@excludes' | sort -nr";
    $command = strtr($command, [
      '@location' => "{$root}/{$files}/",
      '@excludes' => $sandbox->getParameter('excludes', '/js/js_|/css/css_|/php/twig/|/styles/'),
    ]);
    $filesDisk = explode("\n",$sandbox->exec($command));
    $comp = $comp1 = array();
    foreach ($filesDisk as $file) {
      $comp[] = str_replace("$root/$files/", "", $file);
    }
    foreach ($filesManaged as $value) {
      if(strpos($value, 'public://') === 0 || strpos($value, 'private://') === 0){
              $value = str_replace('private://', "", $value);
              $value = str_replace('public://', "", $value);
              $comp1[] = $value;
      }
    }
    $orphan = array_filter(array_diff ($comp, $comp1));

    $count = sizeof($orphan);
    $sandbox->setParameter('issues', implode("\n", $orphan));
    $sandbox->setParameter('files', $count);
    $sandbox->setParameter('plural', $count > 1 ? 's' : '');
    $sandbox->setParameter('prefix', $count > 1 ? 'are' : 'is');

    return $count === 0;
  }

}
