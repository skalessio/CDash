<?php
namespace CDash\Test;

use App\Project;
use CDash\Controller\Auth\Session;
use CDash\ServiceContainer;
use CDash\Test\Traits\CTestXMLSubmissions;
use Illuminate\Support\Facades\Config;
use Symfony\Component\DomCrawler\Crawler;

class InsightExampleTest extends \TestCase
{
  use CTestXMLSubmissions;

  /** @var Project $project */
  protected $project;

  public function setUp()
  {
    parent::setUp();

    $this->baseUrl = Config::get('app.url');
    $this->project = $this->project('InsightExample', [
      'description' => "Project Insight test for cdash testing",
    ]);

    $this->initProjectDirectory('InsightExperimentalExample', 'InsightExample');

    $mock_session = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service = ServiceContainer::getInstance();
    $service->getContainer()->set(Session::class, $mock_session);
  }

  public function testInsightSubmissionsOK()
  {
    $this->assertCTestSubmissionOK('Insight_Experimental_Build')
      ->assertCTestSubmissionOK('Insight_Experimental_Configure')
      ->assertCTestSubmissionOK('Insight_Experimental_Coverage')
      ->assertCTestSubmissionOK('Insight_Experimental_CoverageLog')
      ->assertCTestSubmissionOK('Insight_Experimental_DynamicAnalysis')
      ->assertCTestSubmissionOK('Insight_Experimental_Notes')
      ->assertCTestSubmissionOK('Insight_Experimental_Test');
  }

  public function testGetViewCoverageGivenApiEndpointIndexWithProjectNameAndDate()
  {
    $this->get('/api/v1/index.php?project=InsightExample&date=20090223')
      ->seeJsonContains(['version' => Config::get('cdash.version.string')]);

    $raw = $this->response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('coverages', $json);
    $this->assertNotEmpty($json->coverages);

    $build = $json->coverages[0];

    $this->assertObjectHasAttribute('buildid', $build);
    $this->get("/ajax/getviewcoverage.php?buildid={$build->buildid}&sEcho=1&iColumns=6&sColumns=&iDisplayStart=0&iDisplayLength=25&mDataProp_0=0&mDataProp_1=1&mDataProp_2=2&mDataProp_3=3&mDataProp_4=4&mDataProp_5=5&sSearch=&bRegex=false&sSearch_0=&bRegex_0=false&bSearchable_0=true&sSearch_1=&bRegex_1=false&bSearchable_1=true&sSearch_2=&bRegex_2=false&bSearchable_2=true&sSearch_3=&bRegex_3=false&bSearchable_3=true&sSearch_4=&bRegex_4=false&bSearchable_4=true&sSearch_5=&bRegex_5=false&bSearchable_5=true&iSortCol_0=2&sSortDir_0=asc&iSortingCols=1&bSortable_0=true&bSortable_1=true&bSortable_2=true&bSortable_3=true&bSortable_4=true&bSortable_5=true&status=4&nlow=2&nmedium=3&nsatisfactory=43&ncomplete=32&metricerror=0.49&metricpass=0.7&userid=1&displaylabels=0")
      ->seeJsonContains(['sEcho' => 1]);

    $raw = $this->response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('aaData', $json);
    $this->assertNotEmpty($json->aaData);

    $html = null;
    foreach ($json->aaData as $item) {
      if (strpos($item[0], 'itkCannyEdgesDistanceAdvectionFieldFeatureGenerator.h') !== false) {
        $html = $item[0];
        break;
      }
    }

    $this->assertNotEmpty($html, 'Coverage row does not exist');
    preg_match('/\?buildid=(\d+)&#38;fileid=(\d+)/', $html,$ids);

    $this->assertCount(3, $ids);
    list(, $buildid, $fileid) = $ids;

    $this->visit("viewCoverageFile.php?buildid={$buildid}&fileid={$fileid}");

    // The DOM does not appreciate the "xml" being returned; I'm guessing it's because
    // <!DOCTYPE...> tag prevents XML from discovering a root documentElement, so here we're
    // stripping the xml declaration from the output, then proceeding as usual
    $raw = explode(PHP_EOL, $this->response->getContent());
    $this->resetPageContext();
    $this->response->setContent(implode(PHP_EOL, array_slice($raw, 1)));
    $this->crawler = new Crawler($this->response->getContent(), $this->currentUri);
    $this->see('<span class="normal">    1 | #ifndef __itkNormalVectorDiffusionFunction_txx</span><br><span class="warning">   18</span><span class="normal">    2 | #define __itkNormalVectorDiffusionFunction_txx</span>');
  }
}
