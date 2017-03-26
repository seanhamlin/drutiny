<?php

use PHPUnit\Framework\TestCase;
use Drutiny\Profile\Profile;
use Drutiny\Context;

/**
 * @coversDefaultClass \Drutiny\Command\SiteAudit
 */
class SiteHtmlReportTest extends TestCase {

  protected $profile;
  protected $site = [];
  protected $sites = [];
  protected $context;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    $this->profile = new Profile('Sample title', 'sample', [
      '\Drutiny\Check\Sample\SamplePass' => [],
      '\Drutiny\Check\Sample\SampleWarning' => [],
      '\Drutiny\Check\Sample\SampleFailure' => [],
      '\Drutiny\Check\Sample\SampleException' => [],
    ]);

    $results = [];
    $this->context = new Context();
    $this->context->set('autoRemediate', FALSE);
    foreach ($this->profile->getChecks() as $check => $options) {
      $test = new $check($this->context, $options);
      $result = $test->execute();
      $results[] = $result;
    }

    $this->site['domain'] = 'www.sample.com';
    $this->site['results'] = $results;
  }

  /**
   * @covers ::writeHTMLReport
   * @group report
   */
  public function testSiteHtmlReport() {
    $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../../templates');
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/cache',
      'auto_reload' => TRUE,
    ));
    $filter = new \Twig_SimpleFilter('filterXssAdmin', ['\Drutiny\Command\SiteAudit', 'filterXssAdmin'], [
      'is_safe' => ['html'],
    ]);
    $twig->addFilter($filter);
    $template = $twig->load('site.html.twig');
    $contents = $template->render([
      'profile' => $this->profile,
      'site' => $this->site,
      'sites' => $this->sites,
    ]);

    // Debug.
    // file_put_contents('/tmp/sample.html', $contents);
    // Global report tests.
    $this->assertRegExp('/<h1>Sample title<\/h1>/', $contents);
    $this->assertRegExp('/Report run across www\.sample\.com/', $contents);
    $this->assertRegExp('/&copy; Drutiny \d{4}/', $contents);

    // Pass should pass, and not print remediation.
    $this->assertRegExp('/Sample pass success\./', $contents);
    $this->assertNotRegExp('/Sample pass warning\./', $contents);
    $this->assertNotRegExp('/Sample pass failure\./', $contents);
    $this->assertNotRegExp('/Sample pass descripion\./', $contents);
    $this->assertNotRegExp('/Sample pass remediation\./', $contents);

    // Warning should provide a warning.
    $this->assertRegExp('/Sample warning warning\./', $contents);
    $this->assertNotRegExp('/Sample warning success\./', $contents);
    $this->assertNotRegExp('/Sample warning failure\./', $contents);
    $this->assertNotRegExp('/Sample warning descripion\./', $contents);
    $this->assertNotRegExp('/Sample warning remediation\./', $contents);

    // Failure should fail and print remediation.
    $this->assertRegExp('/Sample failure failure\./', $contents);
    $this->assertNotRegExp('/Sample failure success\./', $contents);
    $this->assertNotRegExp('/Sample failure warning\./', $contents);
    $this->assertRegExp('/Sample failure descripion\./', $contents);
    $this->assertRegExp('/Sample failure remediation\./', $contents);

    // Exception should be caught, and the appropriate text shown. The
    // exception text should not be shown.
    $this->assertRegExp('/Sample exception exception\./', $contents);
    $this->assertNotRegExp('/Sample exception success\./', $contents);
    $this->assertNotRegExp('/Sample exception warning\./', $contents);
    $this->assertRegExp('/Sample exception descripion\./', $contents);
    $this->assertRegExp('/Sample exception remediation\./', $contents);
    $this->assertNotRegExp('/Sample exception text\./', $contents);

    // Ensure no Symfony console HTML is in the report.
    $this->assertNotRegExp('/<info>/', $contents);
    $this->assertNotRegExp('/<comment>/', $contents);
    $this->assertNotRegExp('/<error>/', $contents);
  }

}
