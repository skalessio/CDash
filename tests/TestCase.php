<?php

use App\BuildGroup;
use App\Project;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

  /**
   * This method is to create projects that persist, in other words, its use is
   * very strongly discouraged. Any new tests being written using this method
   * will likely be jeered at an mocked in a PR.
   *
   * @param $name
   * @param array $properties
   * @return Project
   */
    protected function project($name, array $properties = [])
    {
      /** @var Project $project */
      $project = Project::firstOrNew(['name' => $name]);

      if (!$project->id) {
        $default_properties = [
          'autoremovemaxbuilds' => 500,
          'autoremovetimeframe' => 60,
          'coveragethreshold' => 70,
          'cvsviewertype' => 'viewcvs',
          'emailbrokensubmission' => 1,
          'emailmaxchars' => 255,
          'emailmaxitems' => 5,
          'nightlytime' => '01:00:00 UTC',
          'public' => 1,
          'showcoveragecode' => 1,
          'testtimemaxstatus' => 3,
          'testtimestd' => 4,
          'testtimestdthreshold' => 1,
          'uploadquota' => 1024,
        ];
        $attributes = array_merge($default_properties, $properties, $project->getAttributes());
        $project->setRawAttributes($attributes)
          ->save();

        $this->setBuildGroups($project);
        $this->setProjectAdmin($project);
      }

      return $project;
    }

    protected function setBuildGroups(Project $project)
    {
      $groups = [
        'Nightly' => 0,
        'Continuous' => 0,
        'Experimental' => 2,
      ];

      $pos = 0;

      foreach ($groups as $group => $email) {
        $buildGroup = BuildGroup::create([
          'name' => $group,
          'description' => "{$group} builds",
          'summaryemail' => $email,
          'projectid' => $project->id,
        ]);

        DB::table('buildgroupposition')->insert([
          'buildgroupid' => $buildGroup->id,
          'position' => ++$pos,
        ]);

        if ($group === 'Nightly') {
          DB::table('overview_components')->insert([
            'projectid' => $project->id,
            'buildgroupid' => $buildGroup->id,
            'position' => 1,
            'type' => 'build'
          ]);
        }
      }
    }

    protected function setProjectAdmin(Project $project)
    {
      // Add administrator to the project
      DB::table('user2project')->insert([
        'projectid' => $project->id,
        'userid' => 1,
        'role' => 2,
        'emailtype' => 3
      ]);
    }
}
