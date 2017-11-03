<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;

/**
 * Filesystem size
 */
class SystemSize extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();
    $root = $stat['root'];
    $files = $stat['files'];

    $max_size = (int) $sandbox->getParameter('max_size', 2000);
    $warn_size = (int) $sandbox->getParameter('warning_size', 1500);

    $command = "du -s --block-size=1M @location";
    $command = strtr($command, [
      '@location' => "{$root}/{$files}/"
    ]);
    $output = $sandbox->exec($command);
    $size = (int) trim(str_replace("{$root}/{$files}/", "", $output));
    
    $sandbox->setParameter('size', $size);

    if($size > $max_size){
      return FALSE;
    }
    // @todo: Looks like warnings is not working at the moment
    //if($size > $warn_size){
    //  return AuditResponse::WARNING;
    //}
     return TRUE;
    }

}
