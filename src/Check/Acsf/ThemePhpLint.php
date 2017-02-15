<?php

namespace Drutiny\Check\Acsf;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Executor\DoesNotApplyException;
use Drutiny\Annotation\CheckInfo;

/**
 * @CheckInfo(
 *  title = "ACSF theme PHP lint",
 *  description = "All PHP files in the theme should not have syntax errors with them.",
 *  remediation = "Fix syntax errors - :errors.",
 *  success = "No syntax errors found in <code>:valid_count</code> PHP file:valid_plural.",
 *  failure = "Syntax errors found in <code>:error_count</code> PHP file:error_plural.",
 *  exception = "Could not lint the PHP files :exception.",
 *  not_available = "No custom theme is linked.",
 * )
 */
class ThemePhpLint extends Check {

  public function check()
  {
    $root = $this->context->drush->getCoreStatus('root');
    $site = $this->context->drush->getCoreStatus('site');
    // find . -type f -name '*.php' -exec php -l {} \; | grep -v "No syntax errors detected"
    $command = "find {$root}/{$site}/themes/site/ -type f -name '*.php' -exec php -l {} \; || echo 'nope'";
    $output = (string) $this->context->remoteExecutor->execute($command);

    // The ACSF site can have no custom theme repo linked, in which case we
    // will see a "du: cannot access ... No such file or directory" error
    // response. For now, we force this command to not fail using an or
    // statement.
    if (preg_match('/^nope$/', $output)) {
      throw new DoesNotApplyException();
    }

    // Output from find is a giant string with newlines to seperate the files.
    $rows = explode("\n", $output);
    $rows = array_map('trim', $rows);

    $valid = 0;
    $syntax_errors = [];
    foreach ($rows as $row) {
      if (preg_match('/^No syntax errors detected in/', $row)) {
        $valid++;
      }
      else {
        $syntax_errors[] = $row;
      }
    }

    $this->setToken('errors', implode(', ', $syntax_errors));
    $this->setToken('valid_count', $valid);
    $this->setToken('valid_plural', $valid > 1 ? 's' : '');
    $this->setToken('error_count', count($syntax_errors));
    $this->setToken('error_plural', count($syntax_errors) > 1 ? 's' : '');

    if (count($syntax_errors) > 0) {
      return AuditResponse::AUDIT_FAILURE;
    }

    return AuditResponse::AUDIT_SUCCESS;
  }
}
