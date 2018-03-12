<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Scan files in a directory for matching criteria.
 * @Param(
 *  name = "directory",
 *  description = "Absolute filepath to directory to scan",
 *  type = "string",
 *  default = "%root"
 * )
 * @Param(
 *  name = "exclude",
 *  description = "Absolute filepaths to directories omit from scanning",
 *  type = "array",
 *  default = {}
 * )
 * @Param(
 *  name = "filetypes",
 *  description = "file extensions to include in the scan",
 *  type = "array",
 *  default = {}
 * )
 * @Param(
 *   name = "patterns",
 *   description = "patterns to run over each matching file.",
 *   type = "array",
 *   default = {}
 * )
 * @Param(
 *   name = "whitelist",
 *   description = "Whitelist patterns which the 'patterns' parameter may yield false positives from",
 *   type = "array",
 *   default = {}
 * )
 * @Token(
 *   name = "results",
 *   description = "An array of results matching the scan criteria. Each match is an assoc array with the following keys: filepath, line, code, basename.",
 *   type = "array",
 *   default = {}
 * )
 */
class CodeScan extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $directory = $sandbox->getParameter('directory', '%root');
    $stat = $sandbox->drush(['format' => 'json'])->status();

    $directory =  strtr($directory, $stat['%paths']);


    $command = ['find', $stat['%paths']['%root']];

    $types = $sandbox->getParameter('filetypes', []);

    if (!empty($types)) {
      $command[] = '-regex';
      $command[] = "'.*\.\(" . implode('\|', $sandbox->getParameter('filetypes', ['php'])) . "\)'";
    }

    foreach ($sandbox->getParameter('exclude', []) as $filepath) {
      $filepath = strtr($filepath, $stat['%paths']);
      $command[] = "! -path '$filepath'";
    }

    $command[] = '| xargs grep -nE';
    $command[] = '"' . implode('|', $sandbox->getParameter('patterns', [])) . '" || exit 0';

    $whitelist = $sandbox->getParameter('whitelist', []);
    if (!empty($whitelist)) {
      $command[] = "| grep -vE '" . implode('|', $whitelist) . "'";
    }


    $command = implode(' ', $command);
    // echo $command;die;
    $output = $sandbox->exec($command);

    if (empty($output)) {
      return TRUE;
    }

    $matches = array_filter(explode(PHP_EOL, $output));
    $matches = array_map(function ($line) {
      list($filepath, $line_number, $code) = explode(':', $line, 3);
      return [
        'file' => $filepath,
        'line' => $line_number,
        'code' => trim($code),
        'basename' => basename($filepath)
      ];
    }, $matches);

    $results = [
      'found' => count($matches),
      'findings' => $matches,
      'filepaths' => array_values(array_unique(array_map(function ($match) use ($stat) {
        return str_replace($stat['%paths']['%root'], '', $match['file']);
      }, $matches)))
    ];

    $sandbox->setParameter('results', $results);

    return empty($matches);
  }

}
