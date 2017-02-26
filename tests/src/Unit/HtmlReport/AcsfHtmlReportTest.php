<?php

use PHPUnit\Framework\TestCase;
use Drutiny\Profile\ProfileController;
use Drutiny\Profile\Profile;
use Drutiny\Context;
use Drutiny\Annotation\CheckInfo;

/**
 * @coversDefaultClass \Drutiny\Command\AcsfAudit
 */
class AcsfHtmlReportTest extends TestCase
{

  protected $profile;
  protected $site = [];
  protected $sites = [];
  protected $context;

  protected function setUp()
  {
    $this->profile = new Profile('Sample title', 'sample', [
      '\Drutiny\Check\Sample\SamplePass' => [],
      '\Drutiny\Check\Sample\SampleFailure' => [],
    ]);

    $this->context = new Context();

    $domains = ['siteID1' => 'www.sample.com', 'siteID2' => 'www.example.com'];
    foreach ($domains as $id => $domain) {
      $this->sites[$id] = ['domain' => $domain];

      $results = [];
      foreach ($this->profile->getChecks() as $check => $options) {
        $test = new $check($this->context, $options);
        $result = $test->execute();
        $results[] = $result;
      }

      $this->sites[$id]['results'] = $results;
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
    $template = $twig->load('acsf.html.twig');
    $contents = $template->render([
      'profile' => $this->profile,
      'site' => $this->site,
      'sites' => $this->sites,
    ]);

    // Debug.
    //file_put_contents('/tmp/sample.html', $contents);

    // Global report tests.
    $this->assertRegExp('/<h1>Sample title<\/h1>/', $contents);
    $this->assertRegExp('/Report run across 2 sites/', $contents);
    $this->assertRegExp('/&copy; Drutiny \d{4}/', $contents);

    // Print the site IDs.
    $this->assertRegExp('/<th rowspan="2">siteID1<\/th>/', $contents);
    $this->assertRegExp('/<th rowspan="2">siteID2<\/th>/', $contents);

    // Pass should pass, and not print remediation.
    $this->assertRegExp('/Sample pass success\./', $contents);
    $this->assertNotRegExp('/Sample pass failure\./', $contents);
    $this->assertNotRegExp('/Sample pass descripion\./', $contents);
    $this->assertNotRegExp('/Sample pass remediation\./', $contents);

    // Failure should fail and print remediation.
    $this->assertRegExp('/Sample failure failure\./', $contents);
    $this->assertNotRegExp('/Sample failure success\./', $contents);
    $this->assertRegExp('/Sample failure descripion\./', $contents);
    $this->assertRegExp('/Sample failure remediation\./', $contents);

    // Ensure no Symfony console HTML is in the report.
    $this->assertNotRegExp('/<info>/', $contents);
    $this->assertNotRegExp('/<comment>/', $contents);
    $this->assertNotRegExp('/<error>/', $contents);
  }
}
