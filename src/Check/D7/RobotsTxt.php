<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\AuditResponse\AuditResponse;

/**
 * @Drutiny\Annotation\CheckInfo(
 *  title = "Robots.txt",
 *  description = "Ensuring that if you are running the robotstxt module if has the correct content for the robots.txt file.",
 *  remediation = "Run this check with auto remediation enabled.",
 *  not_available = "Robots.txt is not enabled.",
 *  success = "Robots.txt is correct.:fixups",
 *  failure = "Robots.txt is not set correctly, currently set to <code>:current</code>.",
 *  exception = "Could not determine robotstxt settings.",
 *  supports_remediation = TRUE,
 * )
 */
class RobotsTxt extends Check {

  /**
   * Contains a reference to the desired robots.txt contents.
   * @var string
   */
  private $desired = '';

  /**
   * @inheritDoc
   */
  public function check() {
    if (!$this->context->drush->moduleEnabled('robotstxt')) {
      return AuditResponse::AUDIT_NA;
    }

    $current = $this->context->drush->getVariable('robotstxt', '');
    $this->desired = $this->getOption('robotstxt', $this->getDefaultRobotsTxt());
    $this->setToken('current', $current);
    $this->setToken('desired', $this->desired);

    return $current === $this->desired;
  }

  /**
   * @inheritDoc
   */
  public function remediate() {
    $res = $this->context->drush->executePhp("variable_set('robotstxt', base64_decode('" . base64_encode($this->desired) . "'));");
    return $res->isSuccessful();
  }

  /**
   * Returns the latest version of robots.txt from Drupal 7 core.
   *
   * @see https://github.com/drupal/drupal/blob/7.x/robots.txt
   *
   * @return string
   *   The default robots.txt value from Drupal 7 core.
   */
  private function getDefaultRobotsTxt() {
    // This rather large chunk of text was made with the command:
    // curl -s https://raw.githubusercontent.com/drupal/drupal/7.x/robots.txt | sed -e ':a' -e 'N' -e '$!ba' -e 's/\n/\\n/g' -e 's/\"/\\"/g'
    return "#\n# robots.txt\n#\n# This file is to prevent the crawling and indexing of certain parts\n# of your site by web crawlers and spiders run by sites like Yahoo!\n# and Google. By telling these \"robots\" where not to go on your site,\n# you save bandwidth and server resources.\n#\n# This file will be ignored unless it is at the root of your host:\n# Used:    http://example.com/robots.txt\n# Ignored: http://example.com/site/robots.txt\n#\n# For more information about the robots.txt standard, see:\n# http://www.robotstxt.org/robotstxt.html\n\nUser-agent: *\nCrawl-delay: 10\n# CSS, JS, Images\nAllow: /misc/*.css$\nAllow: /misc/*.css?\nAllow: /misc/*.js$\nAllow: /misc/*.js?\nAllow: /misc/*.gif\nAllow: /misc/*.jpg\nAllow: /misc/*.jpeg\nAllow: /misc/*.png\nAllow: /modules/*.css$\nAllow: /modules/*.css?\nAllow: /modules/*.js$\nAllow: /modules/*.js?\nAllow: /modules/*.gif\nAllow: /modules/*.jpg\nAllow: /modules/*.jpeg\nAllow: /modules/*.png\nAllow: /profiles/*.css$\nAllow: /profiles/*.css?\nAllow: /profiles/*.js$\nAllow: /profiles/*.js?\nAllow: /profiles/*.gif\nAllow: /profiles/*.jpg\nAllow: /profiles/*.jpeg\nAllow: /profiles/*.png\nAllow: /themes/*.css$\nAllow: /themes/*.css?\nAllow: /themes/*.js$\nAllow: /themes/*.js?\nAllow: /themes/*.gif\nAllow: /themes/*.jpg\nAllow: /themes/*.jpeg\nAllow: /themes/*.png\n# Directories\nDisallow: /includes/\nDisallow: /misc/\nDisallow: /modules/\nDisallow: /profiles/\nDisallow: /scripts/\nDisallow: /themes/\n# Files\nDisallow: /CHANGELOG.txt\nDisallow: /cron.php\nDisallow: /INSTALL.mysql.txt\nDisallow: /INSTALL.pgsql.txt\nDisallow: /INSTALL.sqlite.txt\nDisallow: /install.php\nDisallow: /INSTALL.txt\nDisallow: /LICENSE.txt\nDisallow: /MAINTAINERS.txt\nDisallow: /update.php\nDisallow: /UPGRADE.txt\nDisallow: /xmlrpc.php\n# Paths (clean URLs)\nDisallow: /admin/\nDisallow: /comment/reply/\nDisallow: /filter/tips/\nDisallow: /node/add/\nDisallow: /search/\nDisallow: /user/register/\nDisallow: /user/password/\nDisallow: /user/login/\nDisallow: /user/logout/\n# Paths (no clean URLs)\nDisallow: /?q=admin/\nDisallow: /?q=comment/reply/\nDisallow: /?q=filter/tips/\nDisallow: /?q=node/add/\nDisallow: /?q=search/\nDisallow: /?q=user/password/\nDisallow: /?q=user/register/\nDisallow: /?q=user/login/\nDisallow: /?q=user/logout/";
  }

}
