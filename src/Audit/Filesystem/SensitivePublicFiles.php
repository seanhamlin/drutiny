<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;

/**
 * Sensitive public files
 */
class SensitivePublicFiles extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $root = $stat['root'];
    $files = $stat['files'];

    $extensions = $sandbox->getParameter('extensions');
    $extensions = array_map('trim', explode(',', $extensions));

    // Output is in the format:
    //
    // 7048 ./iStock_000017426795Large-2.jpg
    // 6370 ./portrait-small-1.png
    //
    // Note, the size is in KB in the response, we convert to MB later on in
    // this check.

    $command = "cd @location ; find . -type f \( @name-lookups \) -printf '@print-format'";
    $command .= " | grep -v -E '/js/js_|/css/css_|/php/twig/' | sort -nr";
    $command = strtr($command, [
      '@location' => "{$root}/{$files}/",
      '@name-lookups' => "-name '*." . implode("' -o -name '*.", $extensions) . "'",
      '@print-format' => '%k\t%p\n',
    ]);

    $output = $sandbox->exec($command);

    if (empty($output)) {
      return AuditResponse::SUCCESS;
    }

    // Output from find is a giant string with newlines to separate the files.
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
