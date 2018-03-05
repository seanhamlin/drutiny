<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;

/**
 * Large files
 */
class FsSize extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $path = $sandbox->getParameter('path', '%files');
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $path = strtr($path, $stat['%paths']);

    $root = $stat['root'];
    $files = $stat['files'];

    $max_size = (int) $sandbox->getParameter('max_size', 20);

    $command = "find @location -type f -size +@sizeM -printf '@print-format'";
    $command .= " | sort -nr";
    $command = strtr($command, [
      '@location' => "{$root}/{$files}/",
      '@size' => $max_size,
      '@print-format' => '%k\t%p\n',
    ]);

    $output = $sandbox->exec($command);

    if (empty($output)) {
      return TRUE;
    }

    // Output from find is a giant string with newlines to seperate the files.
    $rows = array_map(function ($line) {
      $parts = array_map('trim', explode("\t", $line));
      $size = number_format((float) $parts[0] / 1024, 2);
      $filename = trim($parts[1]);
      return "{$filename} [{$size} MB]";
    },
    array_filter(explode("\n", $output)));

    $sandbox->setParameter('issues', $rows);
    $sandbox->setParameter('plural', count($rows) > 1 ? 's' : '');

    return Audit::WARNING;
  }

}
