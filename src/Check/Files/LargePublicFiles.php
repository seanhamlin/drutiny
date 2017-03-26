<?php

namespace Drutiny\Check\Files;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Large public files",
 *  description = "Large static assets should ideally be housed in other services, e.g. Amazon S3 (for files) or Youtube (for videos).",
 *  remediation = "Either delete the files if they are not needed, or look to house them in a more appropriate location. Note, all the above large public files have a public URL and can be downloaded, ensure you do not have any sensitive information in there.",
 *  success = "No large public files found.",
 *  failure = "Large public file:plural found - <ul><li><code>:issues</code></li></ul>",
 *  exception = "Could not determine if there were large public files.",
 * )
 */
class LargePublicFiles extends Check {

  /**
   * @inheritdoc
   */
  public function check() {
    $root = $this->context->drush->getCoreStatus('root');
    $files = $this->context->drush->getCoreStatus('files');

    // Output is in the format:
    //
    // 7048 ./iStock_000017426795Large-2.jpg
    // 6370 ./portrait-small-1.png
    //
    // Note, the size is in KB in the response, we convert to MB later on in
    // this check.
    $max_size = (int) $this->getOption('max_size', 20);
    $command = "cd {$root}/{$files}/ ; find . -type f -size +{$max_size}M -printf '%k\\t%p\\n' | sort -nr";
    $output = (string) $this->context->remoteExecutor->execute($command);

    if (empty($output)) {
      return AuditResponse::AUDIT_SUCCESS;
    }

    // Output from find is a giant string with newlines to seperate the files.
    $rows = explode("\n", $output);
    $rows = array_map('trim', $rows);

    // Split by tab.
    $files = [];
    foreach ($rows as $row) {
      $parts = explode("\t", $row);
      $size = number_format((float) $parts[0] / 1024, 2);
      $filename = trim($parts[1]);
      $files[] = "{$filename} [{$size} MB]";
    }

    $this->setToken('issues', implode('</code></li><li><code>', $files));
    $this->setToken('plural', count($files) > 1 ? 's' : '');

    return AuditResponse::AUDIT_FAILURE;
  }

}
