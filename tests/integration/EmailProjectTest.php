<?php
namespace CDash\Test;

use App\Project;
use App\User;
use CDash\Test\Traits\CTestXMLSubmissions;
use CDash\Test\Traits\ExpectsEmail;
use Illuminate\Support\Facades\DB;

class EmailProjectTest extends \TestCase
{
  use ExpectsEmail, CTestXMLSubmissions;

  private $project;
  private $user;

  public function setUp()
  {
    parent::setUp();

    $this->listenForEmail();
    $this->initProjectDirectory('EmailProjectExample');

    /* The following methods will check exists before creating */
    $this->createProject();
    $this->createUser();
  }

  /**
   * Special Case:
   *   Notice that DatabaseTransactions is not present. This is because we need for this test's
   *   data to persist for backwards compatibility. We also only want the project to be created
   *   once.
   */
  protected function createProject()
  {
    $project = DB::table('project')
      ->select('id')
      ->where(['name' => 'EmailProjectExample'])
      ->first();

    if (!$project) {
      $project = factory(Project::class)->create([
        'name' => 'EmailProjectExample',
        'description' => 'Project EmailProjectExample test for cdash testing',
        'uploadquota' => 1073741824,
      ]);

      // add build groups
      $groups = ['Nightly' => 0, 'Continuous' => 0, 'Experimental' => 2];
      foreach ($groups as $group => $email) {
        DB::table('buildgroup')->insert([
          'name' => $group,
          'projectid' => $project->id,
          'description' => "{$group} builds",
          'summaryemail' => $email
        ]);
      }
    }
    $this->project = $project->id;
  }

  /**
   * Special Case:
   *   Notice that DatabaseTransactions is not present. This is because we need for this test's
   *   data to persist for backwards compatibility. We also only want the user and the entry
   *   in the pivot table to be created once.
   */
  protected function createUser()
  {
    $user = DB::table('user')
      ->select('id')
      ->where(['email' => 'user1@kw'])
      ->first();

    if (!$user) {
      $user = factory(User::class)->create([
        'email' => 'user1@kw',
        'password' => \CDash\Model\User::PasswordHash('user1'),
      ]);

      $admin = DB::table('user')
        ->select('id')
        ->where(['email' => 'simpletest@localhost'])
        ->first();

      DB::table('user2project')
        ->insert([
          [
            'userid' => $user->id,
            'projectid' => $this->project,
            'emailsuccess' => 1,
            'emailtype' => 1,
            'emailcategory' => 126,
            'role' => 0
          ],
          [
            'userid' => $admin->id,
            'projectid' => $this->project,
            'emailsuccess' => 0,
            'emailtype' => 3,
            'emailcategory' => 126,
            'role' => 2
          ],
        ]);

      DB::table('user2repository')
        ->insert([
          'userid' => $user->id,
          'credential' => 'user1kw',
          'projectid' => $this->project,
        ]);
    }

    $this->user = $user->id;
  }

  public function testSubmissionBuildWarningsSent()
  {
    $this->assertCTestSubmissionOK('1_build')
         ->assertCTestSubmissionOK('1_configure');

    $expect_in_body = [
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:03 EST',
      'Type: Nightly',
      'Warnings: 10',
    ];

    // This is dumb but exists merely to demonstrate the fluent nature of the assertion
    // should you need it
    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject('FAILED (w=10): EmailProjectExample - Win32-MSVC2009 - Nightly')
      ->contains($expect_in_body[0])
      ->contains($expect_in_body[1])
      ->contains($expect_in_body[2])
      ->contains($expect_in_body[3])
      ->contains($expect_in_body[4])
      ->contains($expect_in_body[5]);

    $this->assertEmailCount(1);
  }

  public function testSubmissionTestFailureEmailsSent()
  {
    $this->assertCTestSubmissionOK('1_test');

    $expect_in_body = [
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:02 EST',
      'Type: Nightly',
      'Tests not passing: 5',
      'DashboardSendTest',
      'SystemInfoTest',
      'FileActionsTest',
      'StringActionsTest',
      'MathActionsTest',
    ];

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject('FAILED (t=5): EmailProjectExample - Win32-MSVC2009 - Nightly')
      ->contains($expect_in_body);

    $this->assertEmailCount(1);
  }

  public function testBuildTwoSendsFixedEmailToAuthorOfFixes()
  {
    $this->assertCTestSubmissionOK('2_build')
         ->assertCTestSubmissionOK('2_update');

    $expect_in_body = [
      'has fixed build warnings',
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:04 EST',
      'Type: Nightly',
      'Warning fixed: 6',
    ];

    $this->assertEmailSent()
      ->to('user1@kw')
      ->withSubject('PASSED (w=6): EmailProjectExample - Win32-MSVC2009 - Nightly')
      ->contains($expect_in_body);

    $this->assertEmailCount(1);
  }

  public function testTestSubmissionSendsFixedEmailToAuthorOfFixes()
  {
    $this->assertCTestSubmissionOK('2_test');

    $expect_in_body = [
      'a submission to CDash for the project EmailProjectExample has fixed failing tests',
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:04 EST',
      'Type: Nightly',
      'Tests fixed: 2',
    ];

    $this->assertEmailSent()
      ->to('user1@kw')
      ->withSubject('PASSED (t=2): EmailProjectExample - Win32-MSVC2009 - Nightly')
      ->contains($expect_in_body);

    $this->assertEmailCount(1);
  }

  public function testDynamicAnalysisSubmissionSendsEmailToSubscribers()
  {
    $this->assertCTestSubmissionOK('2_dynamicanalysis');
    $subject = 'FAILED (w=3, t=3, d=10): EmailProjectExample - Win32-MSVC2009 - Nightly';
    $expect_in_body = [
      'A submission to CDash for the project EmailProjectExample has build warnings and failing tests and failing dynamic analysis tests.',
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:04 EST',
      'Type: Nightly',
      'Warnings: 3',
      'Tests not passing: 3',
      'Dynamic analysis tests failing: 10',
      '*Warnings*',
      '*Tests failing*',
      'DashboardSendTest',
      'StringActionsTest',
      'MathActionsTest',
      '*Dynamic analysis tests failing or not run* (first 5)',
      'itkGeodesicActiveContourLevelSetSegmentationModuleTest1',
      'itkShapeDetectionLevelSetSegmentationModuleTest1',
      'itkShapeDetectionLevelSetSegmentationModuleTest2',
      'itkVectorFiniteDifferenceFunctionTest1',
      'itkVectorLevelSetFunctionTest2'
    ];

    $this->assertEmailSent()
      ->to('user1@kw')
      ->withSubject($subject)
      ->contains($expect_in_body);

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($expect_in_body);

    $this->assertEmailCount(2);
  }

  public function testSubmissionSendsEmailToCommiter()
  {
    $this->assertCTestSubmissionOK('3_update')
         ->assertCTestSubmissionOK('3_test');

    $subject = 'FAILED (t=4): EmailProjectExample - Win32-MSVC2009 - Nightly';
    $expect_in_body = [
      'the project EmailProjectExample has failing tests',
      'Project: EmailProjectExample',
      'Site: Dash20.kitware',
      'Build Name: Win32-MSVC2009',
      'Build Time: 2009-02-23T05:02:05 EST',
      'Type: Nightly',
      'Tests not passing: 4',
      '*Tests failing*',
      'curl',
      'DashboardSendTest',
      'StringActionsTest',
      'MathActionsTest',
    ];

    $this->assertEmailSent()
      ->to('user1@kw')
      ->withSubject($subject)
      ->contains($expect_in_body);

    $this->assertEmailSent()
      ->to('simpletest@localhost')
      ->withSubject($subject)
      ->contains($expect_in_body);

    $this->assertEmailCount(2);
  }
}


