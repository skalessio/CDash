<?php
namespace CDash\Test;


use App\Project;
use CDash\Controller\Auth\Session;
use CDash\ServiceContainer;
use CDash\Test\Traits\CTestXMLSubmissions;
use Illuminate\Support\Facades\Config;

class BatchmakeTest extends \TestCase
{
  use CTestXMLSubmissions;

  /** @var Project $project*/
  protected $project;

  public function setUp()
  {
    parent::setUp();

    $this->project = $this->project('BatchmakeExample', [
      'description' => "Project Batchmake's test for cdash testing",
    ]);

    $this->initProjectDirectory('BatchmakeNightlyExample', 'BatchmakeExample');

    $mock_session = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service = ServiceContainer::getInstance();
    $service->getContainer()->set(Session::class, $mock_session);
  }

  public function testBatchMakeSubmissionsOK()
  {
    $this->assertCTestSubmissionOK('BatchMake_Nightly_Build')
      ->assertCTestSubmissionOK('BatchMake_Nightly_Configure')
      ->assertCTestSubmissionOK('BatchMake_Nightly_Notes')
      ->assertCTestSubmissionOK('BatchMake_Nightly_Test')
      ->assertCTestSubmissionOK('BatchMake_Nightly_Update')
      ->seeInDatabase('build',
        [
          'stamp' => '20090223-0100-Nightly',
          'name' => 'Win32-MSVC2009',
          'type' => 'Nightly',
          'generator' => 'ctest2.6-patch 0',
          'command' => 'F:\PROGRA~1\MICROS~1.0\Common7\IDE\VCExpress.exe BatchMake.sln /build Release /project ALL_BUILD',
        ]
      );
  }

  public function testSiteViewGivenApiEndpointIndexWithProjectNameAndDate()
  {
    $this->get('/api/v1/index.php?project=BatchmakeExample&date=20090223')
      ->seeJsonContains(['version' => Config::get('cdash.version.string')]);

    $raw = $this->response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('buildgroups', $json);
    $this->assertNotEmpty($json->buildgroups);

    $buildgroup = $json->buildgroups[0];

    $this->assertObjectHasAttribute('builds', $buildgroup);
    $this->assertNotEmpty($buildgroup->builds);

    $build = $buildgroup->builds[0];

    $this->assertObjectHasAttribute('siteid', $build);
    $this->assertGreaterThan(0, $build->siteid);
    $query = [
      'siteid' => $build->siteid,
      'projectid' => $this->project->id,
      'currenttime' => '1235354400',
    ];

    $this->call('GET', '/viewSite.php', $query);
    $this->seeStatusCode(200)
      ->seeText('Total Physical Memory: 15MiB');
  }

  public function testBuildSummaryViewGivenApiEndpointWithProjectNameAndDate()
  {
    $this->get('/api/v1/index.php?project=BatchmakeExample&date=20090223')
      ->seeJsonContains(['version' => Config::get('cdash.version.string')]);

    $raw = $this->response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('buildgroups', $json);
    $this->assertNotEmpty($json->buildgroups);

    $buildgroup = $json->buildgroups[0];

    $this->assertObjectHasAttribute('builds', $buildgroup);
    $this->assertNotEmpty($buildgroup->builds);

    $build = $buildgroup->builds[0];

    $this->get("/api/v1/buildSummary.php?buildid={$build->id}")
      ->seeText('warning C4068: unknown pragma');
  }
}
