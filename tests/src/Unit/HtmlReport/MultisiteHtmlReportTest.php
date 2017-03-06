<?php

use PHPUnit\Framework\TestCase;
use Drutiny\Profile\ProfileController;
use Drutiny\Profile\Profile;
use Drutiny\Context;
use Drutiny\Annotation\CheckInfo;

/**
 * @coversDefaultClass \Drutiny\Command\MultisiteAudit
 */
class MultisiteHtmlReportTest extends TestCase
{

  protected $profile;
  protected $site = [];
  protected $sites = [];
  protected $context;

  protected function setUp()
  {
    $this->profile = new Profile('Sample title', 'sample', [
      '\Drutiny\Check\Sample\SamplePass' => [],
      '\Drutiny\Check\Sample\SampleWarning' => [],
      '\Drutiny\Check\Sample\SampleFailure' => [],
    ]);

    $this->context = new Context();

    $domains = ['www.sample.com', 'www.example.com'];
    foreach ($domains as $domain) {
      $this->sites[$domain] = ['domain' => $domain];

      $results = [];
      foreach ($this->profile->getChecks() as $check => $options) {
        $test = new $check($this->context, $options);
        $result = $test->execute();
        $results[] = $result;
      }

      $this->sites[$domain]['results'] = $results;
    }
  }

  /**
   * @covers ::writeHTMLReport
   * @group report
   */
  public function testSiteHtmlReport()
  {
    $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../../templates');
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/cache',
      'auto_reload' => true,
    ));
    $filter = new \Twig_SimpleFilter('filterXssAdmin', ['\Drutiny\Command\SiteAudit', 'filterXssAdmin'], [
      'is_safe' => ['html'],
    ]);
    $twig->addFilter($filter);
    $template = $twig->load('multisite.html.twig');
    $contents = $template->render([
      'profile' => $this->profile,
      'site' => $this->site,
      'sites' => $this->sites,
    ]);

    // Debug.
    //file_put_contents('/tmp/sample.html', $contents);

    // Global report tests.
    $this->assertRegExp('/<h1>Sample title<\/h1>/', $contents);
    $this->assertRegExp('/Report run across <strong>2<\/strong> sites/', $contents);
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

    // Ensure no Symfony console HTML is in the report.
    $this->assertNotRegExp('/<info>/', $contents);
    $this->assertNotRegExp('/<comment>/', $contents);
    $this->assertNotRegExp('/<error>/', $contents);
  }
}
