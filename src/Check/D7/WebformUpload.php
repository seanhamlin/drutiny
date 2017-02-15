<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Annotation\CheckInfo;
use Drutiny\Executor\DoesNotApplyException;

/**
 * @CheckInfo(
 *  title = "Webform upload",
 *  description = "Spammers are known to want to uplaod files to webforms that allow anonymous user users access.",
 *  remediation = "Restrict upload types, enforce a max upload size, use a random folder underneath <code>/webform/</code> to store the uploads.",
 *  not_available = "Webform is not enabled.",
 *  success = "There are no files uploaded that look malicious.",
 *  failure = "There :prefix <code>:number_of_silly_uploads</code> malicious webform upload:plural.:files",
 *  exception = "Could not determine the amount of malicious uploads.",
 * )
 */
class WebformUpload extends Check {
  public function check() {

    if (!$this->context->drush->moduleEnabled('webform')) {
      throw new DoesNotApplyException('webform is not enabled.');
    }

    // Look for NFL uploads.
    $output = $this->context->drush->sqlQuery("SELECT filename FROM {file_managed} WHERE UPPER(filename) LIKE '%NFL%' AND status = 0;");
    $output = array_filter($output);
    if (empty($output)) {
      $number_of_silly_uploads = 0;
      $this->setToken('files', '');
    }
    else {
      $number_of_silly_uploads = count($output);
      $this->setToken('files', '<br><br><code>' .  implode('</code>, <code>' , $output) . '</code>');
    }
    $this->setToken('number_of_silly_uploads', $number_of_silly_uploads);
    $this->setToken('plural', $number_of_silly_uploads > 1 ? 's' : '');
    $this->setToken('prefix', $number_of_silly_uploads > 1 ? 'are' : 'is');

    return $number_of_silly_uploads === 0;
  }
}
