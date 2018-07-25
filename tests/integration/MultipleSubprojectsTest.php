<?php
namespace CDash\Test;

use CDash\Controller\Auth\Session;
use CDash\ServiceContainer;
use CDash\System;
use CDash\Test\Traits\ExpectsEmail;
use CDash\Test\Traits\CTestXMLSubmissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MultipleSubprojectsTest extends \TestCase
{
  use ExpectsEmail, CTestXMLSubmissions;

  private $builds;

  public function setUp()
  {
    parent::setUp();

    $this->initProjectDirectory('MultipleSubprojects', 'SubProjectExample');
    $this->project = DB::table('project')
      ->select('id')
      ->where('name', '=', 'SubProjectExample')
      ->first();

    $mock_session = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->getMock();

    $mock_system = $this->getMockBuilder(System::class)
      ->disableOriginalConstructor()
      ->getMock();

    /** @var \DI\Container $container */
    $container = ServiceContainer::getInstance()
      ->getContainer();

    $container->set(Session::class, $mock_session);
    $container->set(System::class, $mock_system);
  }

  public function tearDown()
  {
    if (!empty($this->builds)) {
      foreach($this->builds as $build) {
        DB::table('build')->where('id', $build->id)->delete();
      }
    }
    parent::tearDown();
  }

  public function submitCTestFiles()
  {
    $this->assertCTestSubmissionOK('Project')
         ->assertCTestSubmissionOK('Configure')
         ->assertCTestSubmissionOK('Build')
         ->assertCTestSubmissionOK('Coverage')
         ->assertCTestSubmissionOK('CoverageLog')
         ->assertCTestSubmissionOK('DynamicAnalysis')
         ->assertCTestSubmissionOK('Test')
         ->assertCTestSubmissionOK('Notes')
         ->assertCTestSubmissionOK('Upload');

    $this->builds = DB::table('build')
      ->select(['id', 'buildduration', 'configureduration'])
      ->where('name', '=', 'CTestTest-Linux-c++-Subprojects')
      ->get();

    $this->assertCount(5, $this->builds);

    foreach($this->builds as $build) {
      if ($build->buildduration != 5) {
        $this->fail("Expected 5 but found {$build->buildduration} for {$build->id}'s build duration");
      }

      if ($build->configureduration != 1) {
        $this->fail("Expected 1 but found {$build->configureduration}  for {$build->id}'s configure duration");
      }
    }
  }

  public function testApiEndpointIndexGivenProjectNameAndDate()
  {
    $this->submitCTestFiles();

    $uri = '/api/v1/index.php?project=SubProjectExample&date=2016-07-28';

    $this->get($uri);
    $response = $this->response;

    $exception = is_null($response->exception) ? null : $response->exception->getMessage();

    $this->assertNull($exception, $exception);
    $this->assertResponseStatus(200);


    $raw = $response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('buildgroups', $json);
    $this->assertCount(1, $json->buildgroups);

    $buildgroup = $json->buildgroups[0];

    $this->assertObjectHasAttribute('builds', $buildgroup);
    $this->assertCount(1, $buildgroup->builds);

    $build = $buildgroup->builds[0];

    $this->assertGreaterThan(0, $build->id);
    $this->assertEquals(4, $build->numchildren);
    $this->assertEquals(1, $buildgroup->numconfigureerror);
    $this->assertEquals(1, $buildgroup->numconfigurewarning);
    $this->assertEquals(2, $buildgroup->numbuilderror);
    $this->assertEquals(2, $buildgroup->numbuildwarning);
    $this->assertEquals(1, $buildgroup->numtestpass);
    $this->assertEquals(5, $buildgroup->numtestfail);
    $this->assertEquals(1, $buildgroup->numtestnotrun);

    $this->assertObjectHasAttribute('coverages', $json);
    $this->assertCount(1, $json->coverages);

    $covered = $json->coverages[0];
    $this->assertEquals(70, $covered->percentage);
    $this->assertEquals(14, $covered->loctested);
    $this->assertEquals(6, $covered->locuntested);

    $this->assertObjectHasAttribute('dynamicanalyses', $json);
    $this->assertCount(1, $json->dynamicanalyses);

    $da = $json->dynamicanalyses[0];
    $this->assertEquals(3, $da->defectcount);
  }

  public function testApiEndpointIndexGivenProjectNameAndParentId()
  {
    $this->submitCTestFiles();

    $parent = DB::table('build')
      ->select('id')
      ->where('name', 'CTestTest-Linux-c++-Subprojects')
      ->where('parentid', -1)
      ->first();

    $method = 'GET';
    $uri = "/api/v1/index.php?project=SubProjectExample&parentid={$parent->id}";

    $this->get($uri);
    $response = $this->response;

    $exception = is_null($response->exception) ? null : $response->exception->getMessage();

    $this->assertNull($exception, $exception);
    $this->assertResponseStatus(200);

    $raw = $response->getContent();
    $json = json_decode($raw);

    $this->assertObjectHasAttribute('numchildren', $json);
    $this->assertEquals(4, $json->numchildren);

    $this->assertObjectHasAttribute('parenthasnotes', $json);
    $this->assertTrue($json->parenthasnotes);

    $this->assertObjectHasAttribute('uploadfilecount', $json);
    $this->assertEquals(1, $json->uploadfilecount);

    $this->assertObjectHasAttribute('coverages', $json);
    $this->assertCount(2, $json->coverages);

    $this->assertObjectHasAttribute('dynamicanalyses', $json);
    $this->assertCount(3, $json->dynamicanalyses);

    $this->assertObjectHasAttribute('updateduration', $json);
    $this->assertFalse($json->updateduration);

    $this->assertObjectHasAttribute('configureduration', $json);
    $this->assertEquals('1s', $json->configureduration);

    $this->assertObjectHasAttribute('buildduration', $json);
    $this->assertEquals('5s', $json->buildduration);

    $this->assertObjectHasAttribute('testduration', $json);
    $this->assertEquals('4s', $json->testduration);

    $this->assertObjectHasAttribute('buildgroups', $json);
    $this->assertNotEmpty($json->buildgroups);
    $this->assertObjectHasAttribute('builds', $json->buildgroups[0]);
    $this->assertNotEmpty($json->buildgroups[0]->builds);
    $this->assertCount(4, $json->buildgroups[0]->builds);

    $labels = Arr::pluck($json->buildgroups[0]->builds, 'label');

    $this->assertContains('MyExperimentalFeature', $labels);
    $this->assertContains('MyProductionCode', $labels);
    $this->assertContains('MyThirdPartyDependency', $labels);
    $this->assertContains('EmptySubproject', $labels);
  }

  public function testApiEndpointViewSubprojectsGivenProjectNameAndDate()
  {
    $this->submitCTestFiles();

    $uri = '/api/v1/viewSubProjects.php?project=SubProjectExample&date=2016-07-28';
    $this->get($uri);

    $response = $this->response->getContent();
    $json = json_decode($response);

    $project = (object) [
      'nbuilderror' => 1,
      'nbuildwarning' => 1,
      'nbuildpass' => 0,
      'nconfigureerror' => 1,
      'nconfigurewarning' => 1,
      'nconfigurepass' => 0,
      'ntestpass' => 1,
      'ntestfail' => 5,       // Total number of tests failed
      'ntestnotrun' => 1,
    ];

    $subprojects = [
      'MyThirdPartyDependency' => (object) [
        'nbuilderror' => 1,
        'nbuildwarning' => 0,
        'nbuildpass' => 0,
        'nconfigureerror' => 1,
        'nconfigurewarning' => 1,
        'nconfigurepass' => 0,
        'ntestpass' => 0,
        'ntestfail' => 0,
        'ntestnotrun' => 1,
      ],
      'MyExperimentalFeature' => (object) [
        'nbuilderror' => 0,
        'nbuildwarning' => 1,
        'nbuildpass' => 0,
        'nconfigureerror' => 1,
        'nconfigurewarning' => 1,
        'nconfigurepass' => 0,
        'ntestpass' => 0,
        'ntestfail' => 5,
        'ntestnotrun' => 0,
      ],
      'MyProductionCode' => (object) [
        'nbuilderror' => 0,
        'nbuildwarning' => 1,
        'nbuildpass' => 0,
        'nconfigureerror' => 1,
        'nconfigurewarning' => 1,
        'nconfigurepass' => 0,
        'ntestpass' => 1,
        'ntestfail' => 0,
        'ntestnotrun' => 0,
      ],
    ];

    // Check that the project attribute in the response has properties equal the the
    // $project created above
    $this->assertObjectHasAttribute('project', $json);
    $actual = $json->project;
    foreach (get_object_vars($project) as $property => $expected) {
      $this->assertEquals($expected, $actual->{$property});
    }

    // Check that the subprojects attribute in the response has 3 suprojects,
    // MyThirdPartyDependency, MyExperimentalFeature, and MyProduction code
    // whose properties are equal to the properties defined above
    $this->assertObjectHasAttribute('subprojects', $json);
    $this->assertNotEmpty($json->subprojects);
    $names = array_keys($subprojects);

    // Filter out the subprojects we're concerned with
    $actual = Arr::where($json->subprojects, function ($key, $value) use ($names) {
      return in_array($value->name, $names);
    });

    // Loop through each subproject and verify equality of properties
    foreach ($actual as $subproject) {
      $name = $subproject->name;
      foreach(get_object_vars($subprojects[$name]) as $property => $expected) {
        $this->assertEquals($expected, $subproject->{$property});
      }
    }
  }

  public function testApiEndpointViewDynamicAnalysisGivenBuildId()
  {
    $this->submitCTestFiles();

    $expects = [
      'MyThirdPartyDependency' => (object) [
        'analyses' => 1,
        'defect_types' => 1,
        'defects' => 2,
        'defect_type' => 'Memory Leak',
      ],
      'MyExperimentalFeature' => (object) [
        'analyses' => 1,
        'defect_types' => 1,
        'defects' => 1,
        'defect_type' => 'Invalid Pointer Write',
      ],
      'MyProductionCode' => (object) [
        'analyses' => 1,
        'defect_types' => 0,
        'defects' => 0,
        'defect_type' => null,
      ],
    ];

    $builds = DB::table('label')
      ->join('label2build', 'label.id', '=', 'label2build.labelid')
      ->join('build', 'label2build.buildid', '=', 'build.id')
      ->join('dynamicanalysis', 'dynamicanalysis.buildid', '=', 'build.id')
      ->select('text', 'label2build.buildid as id')
      ->whereIn('label.text', array_keys($expects))
      ->where('build.parentid', '>', 0)
      ->get();


    foreach ($expects as $build => $expected) {
      $id = -2;
      foreach ($builds as $row) {
        if ($build === $row->text) {
          $id = $row->id;
        }
      }

      $uri = "/api/v1/viewDynamicAnalysis.php?buildid={$id}";
      $this->get($uri);

      $raw = $this->response->getContent();
      $json = json_decode($raw);

      $this->assertObjectHasAttribute('dynamicanalyses', $json);
      $this->assertCount($expected->analyses, $json->dynamicanalyses);
      $this->assertObjectHasAttribute('defecttypes', $json);
      $this->assertCount($expected->defect_types, $json->defecttypes);
      if ($expected->defects) {
        $analyses = $json->dynamicanalyses[0];
        $this->assertObjectHasAttribute('defects', $analyses);
        $this->assertEquals($expected->defects, $analyses->defects[0]);

        if (is_string($expected->defect_type)) {
          $this->assertEquals($expected->defect_type, $json->defecttypes[0]->type);
        }
      }
    }
  }

  public function testMultipleSubprojectsSubmissionSendsEmails()
  {
    $parameters = DB::table('buildgroup')
      ->join('project', 'buildgroup.projectid', '=', 'project.id')
      ->select('projectid', 'summaryemail', 'emailmaxchars')
      ->where('buildgroup.name', 'Experimental')
      ->where('project.name', 'SubProjectExample')
      ->first();

    DB::table('buildgroup')
      ->where('projectid', $parameters->projectid)
      ->update(['summaryemail' => 0]);

    DB::table('project')
      ->where('id', $parameters->projectid)
      ->update(['emailmaxchars' => 2147483647]);

    $this->listenForEmail();
    $this->submitCTestFiles();

    $email_body = [
      'the project SubProjectExample has configure errors',
      'Project: SubProjectExample',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Configure errors: 1',
      '*Configure*',
      'Status: 1',
      'Output: -- The C compiler identification is GNU 4.8.4',
      '-- Check for working CXX compiler: /usr/bin/c++',
      'CMake Warning: CMake is forcing CMAKE_CXX_COMPILER to',
    ];

    $subject = 'FAILED (c=1): SubProjectExample - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertEmailSent()
      ->to('nox-noemail@noemail')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertEmailSent()
      ->to('optika-noemail@noemail')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has build warnings',
      'Project: SubProjectExample',
      'SubProject: MyExperimentalFeature',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Warnings: 1',
      '*Warnings*',
      'MyExperimentalFeature/experimental.cxx',
      'warning: unused parameter ‘argc’'
    ];

    $subject = 'FAILED (w=1): SubProjectExample/MyExperimentalFeature - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has build warnings',
      'Project: SubProjectExample',
      'SubProject: MyProductionCode',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Warnings: 1',
      '*Warnings*',
      'MyProductionCode/production.cxx',
      'warning: unused parameter ‘argc’',
    ];

    $subject = 'FAILED (w=1): SubProjectExample/MyProductionCode - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has build errors',
      'Project: SubProjectExample',
      'SubProject: MyThirdPartyDependency',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Errors: 2',
      '*Error*',
      'MyThirdPartyDependency/thirdparty.cxx',
      'error: ‘n’ was not declared in this scope',
      'c++: error: CMakeFiles/thirdparty.dir/thirdparty.cxx.o: No such file or directory',
    ];

    $subject = 'FAILED (b=2): SubProjectExample/MyThirdPartyDependency - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertEmailSent()
      ->to('optika-noemail@noemail')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has failing dynamic analysis tests',
      'Project: SubProjectExample',
      'SubProject: MyExperimentalFeature',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Dynamic analysis tests failing: 1',
      '*Dynamic analysis tests failing or not run*',
      'experimentalFail1'
    ];

    $subject = 'FAILED (d=1): SubProjectExample/MyExperimentalFeature - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has failing tests',
      'Project: SubProjectExample',
      'SubProject: MyExperimentalFeature',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Tests not passing: 5',
      '*Tests failing* (first 5)',
      'experimentalFail1 | Completed (Failed) |',
      'experimentalFail2 | Completed (Failed) |',
      'experimentalFail3 | Completed (Failed) |',
      'experimentalFail4 | Completed (Failed) |',
      'experimentalFail5 | Completed (Failed) |',
    ];

    $subject = 'FAILED (t=5): SubProjectExample/MyExperimentalFeature - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertEmailSent()
      ->to('nox-noemail@noemail')
      ->withSubject($subject)
      ->contains($email_body);

    $email_body = [
      'the project SubProjectExample has failing tests',
      'Project: SubProjectExample',
      'SubProject: MyThirdPartyDependency',
      'Site: livonia-linux',
      'Build Name: CTestTest-Linux-c++-Subprojects',
      'Build Time: 2016-07-28T15:32:14 EDT',
      'Type: Experimental',
      'Tests not passing: 1',
      '*Tests not run*',
      'thirdparty',
    ];

    $subject = 'FAILED (t=1): SubProjectExample/MyThirdPartyDependency - CTestTest-Linux-c++-Subprojects - Experimental';

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertEmailSent()
      ->to('optika-noemail@noemail')
      ->withSubject($subject)
      ->contains($email_body);

    $this->assertAllEmailsAccountedFor();

    // leave the database as we found it
    DB::table('buildgroup')
      ->where('projectid', $parameters->projectid)
      ->update(['summaryemail' => $parameters->summaryemail]);

    DB::table('project')
      ->where('id', $parameters->projectid)
      ->update(['emailmaxchars' => $parameters->emailmaxchars]);

  }


  // TODO: find out the purpose of this test?
  public function testViewConfigurePage()
  {
    $this->submitCTestFiles();
    $parent = DB::table('build')
      ->select('id')
      ->where('name', 'CTestTest-Linux-c++-Subprojects')
      ->where('parentid', -1)
      ->first();

    $uri = "/viewConfigure.php?buildid={$parent->id}";

    $this->get($uri);
    $this->assertNotEmpty($this->response->getContent());
  }
}
