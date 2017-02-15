<?php

namespace Drutiny\Check\Acsf;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "ACSF theme size",
 *  description = "In Acquia Cloud Site Factory, you should aim to keep your theme repositories as small as possible to avoid exhausting the disk. Having theme repositories less than 50 MB is recommended.",
 *  remediation = "Reduce the number of unneeded files, e.g. PDFs, and PSDs of any initial designs, any node modules SaaS cache files.",
 *  success = "Theme size is currently smaller than <code>:max_size</code> MB. Actual size is <code>:value</code> MB.",
 *  warning = "Theme size is currently smaller than <code>:max_size</code> MB but larger than <code>:warning_size</code> MB. Actual size is <code>:value</code> MB.",
 *  failure = "Theme size is currently larger than <code>:max_size</code> MB. Actual size is <code>:value</code> MB.",
 *  exception = "Could not determine disk usage of theme.",
 *  not_available = "No custom theme is linked.",
 * )
 */
class ThemeSize extends Check {

  public function check()
  {
    $root = $this->context->drush->getCoreStatus('root');
    $site = $this->context->drush->getCoreStatus('site');
    $command = "du -ms {$root}/{$site}/themes/site/ || echo 'nope'";
    $output = (string) $this->context->remoteExecutor->execute($command);

    // The ACSF site can have no custom theme repo linked, in which case we
    // will see a "du: cannot access ... No such file or directory" error
    // response. For now, we force this command to not fail using an or
    // statement.
    if (preg_match('/^nope$/', $output)) {
      throw new DoesNotApplyException();
    }

    // Output from du here is an int followed by space, followed by the full
    // path. We only want the int (size in MB).
    preg_match('/^(\d+)\s+\/.*/', $output, $matches);
    if (!isset($matches[1])) {
      throw new \Exception('Invalid response from du');
    }

    $size_in_mb = (int) $matches[1];
    $max_size = (int) $this->getOption('max_size', 50);
    $warning_size = (int) $this->getOption('warning_size', 20);
    $this->setToken('max_size', $max_size);
    $this->setToken('warning_size', $warning_size);
    $this->setToken('value', $size_in_mb);

    if ($size_in_mb >= $max_size) {
      return AuditResponse::AUDIT_FAILURE;
    }
    if ($size_in_mb >= $warning_size) {
      return AuditResponse::AUDIT_WARNING;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
