<?php

namespace Drutiny\Check\Drush;

use Drutiny\Check\Check;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Anonymous sessions",
 *  description = "If you are generating sessions for anonymous users, you are causing a major performance impact to your site. Having anonymous sessions will break traditional page caching in Varnish and CDNs.",
 *  remediation = "Find out what modules are causing the sessions, and look to remove them.",
 *  success = "There are no anonymous sessions.",
 *  failure = "There :prefix <code>:number_of_anon_sessions</code> anonymous session:plural. Sample session content: <p><code>:sample</code></p>",
 *  exception = "Could not determine the amount of anonymous sessions.",
 * )
 */
class SessionsAnon extends Check {

  /**
   *
   */
  public function check() {

    // Exclude openid sessions, as these are for people that are trying to login
    // but perhaps have not made it the whole way yet.
    $output = $this->context->drush->sqlQuery("SELECT session FROM {sessions} WHERE uid = 0 AND session NOT LIKE 'openid%' AND session NOT LIKE '%Access denied%';");
    $output = array_filter($output);
    $number_of_anon_sessions = count($output);

    $this->setToken('number_of_anon_sessions', number_format($number_of_anon_sessions));
    $this->setToken('plural', $number_of_anon_sessions > 1 ? 's' : '');
    $this->setToken('prefix', $number_of_anon_sessions > 1 ? 'are' : 'is');

    // Work out a sample session, excluding the column name.
    $sample = '';
    $output = array_slice($output, 0, 10);
    foreach ($output as $index => $row) {
      if ($row === 'session') {
        continue;
      }
      $sample = $row;
      break;
    }

    $this->setToken('sample', $sample);

    return $number_of_anon_sessions === 0;
  }

}
