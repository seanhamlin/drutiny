<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Large files
 * @Param(
 *  name = "max_size",
 *  description = "Report files larger than this value measured in megabytes.",
 *  type = "integer",
 *  default = 20
 * )
 * @Token(
 *  name = "issues",
 *  description = "A list of files that reach the max file size.",
 *  type = "array"
 * )
 * @Token(
 *  name = "plural",
 *  description = "This variable will contain an 's' if there is more than one issue found.",
 *  type = "string",
 *  default = ""
 * )
 */
class LargeFiles extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();

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
