<?php
namespace CDash\Test;

use CDash\Test\Traits\ExpectsEmail;
use CDash\Test\Traits\CTestXMLSubmissions;
use Illuminate\Support\Facades\DB;
use App\Project;

class SubprojectTest extends \TestCase
{
  use ExpectsEmail, CTestXMLSubmissions;

  protected $project;

  public function setUp()
  {
    parent::setUp();
    $this->project = $this->project('SubProjectExample',
      [
        'description' => 'Project SubprojectExample test for cdash testing',
        'emailbrokensubmission' => 1,
        'emailredundantfailures' => 1,
        'uploadquota' => 1024,
      ]);

    $this->listenForEmail();
    $this->initProjectDirectory('SubProjectExample');
  }

  public function testSubmitsProjectFile()
  {
    $this->assertCTestSubmissionOK('Project_1');
  }

  public function testSubmitsBuildFilesAndEmailsWarnings()
  {
    $this->assertCTestSubmissionOK('Build_1');
    $subject = 'FAILED (w=21): SubProjectExample/NOX - Linux-GCC-4.1.2-SERIAL_RELEASE - Nightly';
    $expect_in_body = [
      'the project SubProjectExample has build warnings',
      'Project: SubProjectExample',
      'SubProject: NOX',
      'Site: godel.sandia.gov',
      'Build Name: Linux-GCC-4.1.2-SERIAL_RELEASE',
      'Build Time: 2009-08-06T08:19:56 EDT',
      'Type: Nightly',
      'Warnings: 21',
      '*Warnings* (first 5)',
      'EpetraExt_BlockAdjacencyGraph',
      'EpetraExt_BlockDiagMatrix',
      'EpetraExt_MultiPointModelEvaluator',
      'Galeri_Utils',
      'Galeri_CrsMatrices',
    ];

    $this->assertEmailSent()
         ->to('simpletest@localhost')
         ->withSubject($subject)
         ->contains($expect_in_body);

    $this->assertEmailCount(1);
  }

  public function testSubmitsTestFileAndEmailsTestFailures()
  {
    $this->assertCTestSubmissionOK('Test_1');
    $subject = 'FAILED (t=1): SubProjectExample/NOX - Linux-GCC-4.1.2-SERIAL_RELEASE - Nightly';
    $expect_in_body = [
      'the project SubProjectExample has failing tests',
      'Project: SubProjectExample',
      'SubProject: NOX',
      'Site: godel.sandia.gov',
      'Build Name: Linux-GCC-4.1.2-SERIAL_RELEASE',
      'Build Time: 2009-08-06T08:19:56 EDT',
      'Type: Nightly',
      'Tests not passing: 1',
    ];

    $this->assertEmailSent()
         ->to('simpletest@localhost')
         ->withSubject($subject)
         ->contains($expect_in_body);

    $this->assertEmailSent()
         ->to('nox-noemail@noemail')
         ->withSubject($subject)
         ->contains($expect_in_body);

    $this->assertEmailCount(2);
  }
}
