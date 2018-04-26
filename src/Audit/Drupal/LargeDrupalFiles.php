<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Identify files larger than a specfied size. Pass if no matching files found.
 * @Param(
 *  name = "max_size",
 *  description = "Report files larger than this value measured in megabytes.",
 *  type = "integer",
 *  default = 20
 * )
 * @Token(
 *  name = "total",
 *  description = "Total number of large files found",
 *  type = "integer"
 * )
 * @Token(
 *  name = "too_many_files",
 *  description = "Text to display if there are more than 10 files found.",
 *  type = "integer"
 * )
 * @Token(
 *  name = "files",
 *  description = "A list of up to 10 files that are too large.",
 *  type = "integer"
 * )
 * @Token(
 *  name = "plural",
 *  description = "This variable will contain an 's' if there is more than one issue found.",
 *  type = "string",
 *  default = ""
 * )
 */
class LargeDrupalFiles extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $max_size = (int) $sandbox->getParameter('max_size', 10000000);
    $sandbox->setParameter('readable_max_size', $max_size / 1000 / 1000 . ' MB');
    $query = "SELECT fm.uri, fm.filesize, (SELECT COUNT(*) FROM file_usage fu WHERE fu.fid = fm.fid) as 'usage' FROM file_managed fm WHERE fm.filesize >= @size ORDER BY fm.filesize DESC";
    $query = strtr($query, ['@size' => $max_size]);
    $output = $sandbox->drush()->sqlQuery($query);
print_r($output);
    if (empty($output)) {
      return TRUE;
    }

    $records = is_array($output) ? $output : explode("\n", $output);
    $rows = array();
    foreach ($records as $record) {
      // Ignore record if it contains message about adding RSA key to known hosts.
      if (strpos($record, '(RSA) to the list of known hosts') != FALSE) {
        continue;
      }

      // Create the columns
      $parts = explode("\t", $record);
      $rows[] = [
        'uri' => $parts[0],
        'size' => number_format((float) $parts[1] / 1000 / 1000, 2) . ' MB',
        'usage' => ($parts[2] == 0) ? 'No' : 'Yes'
      ];
    }
    $totalRows = count($rows);

    if ($totalRows < 1) {
      return TRUE;
    }
    $sandbox->setParameter('total', $totalRows);

    // Reduce the number of rows to 10
    $rows = array_slice($rows, 0, 10);
    $too_many_files = ($totalRows > 10) ? "Only the first 10 files are displayed." : "";

    $sandbox->setParameter('too_many_files', $too_many_files);
    $sandbox->setParameter('files', $rows);
    $sandbox->setParameter('plural', $totalRows > 1 ? 's' : '');

    return Audit::FAIL;
  }

}
