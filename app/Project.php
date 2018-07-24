<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class Project
 * @package App
 */
class Project extends Model
{
  /** @var string $table */
  protected $table = 'project';

  /** @var bool $timestamps */
  public $timestamps = false;

  /**
   * @param string|null $message
   * @return bool
   */
  public function isAtBuildCapacity()
  {
    $max_builds = Config::get('cdash.projects.max_builds');
    $admin_email = Config::get('cdash.admin.email');

    if ($this->hasUnlimitedBuilds()) {
      return false;
    }

    if ($this->builds()->count() < $max_builds) {
      return false;
    }

    Log::info("Too many builds for {$this->name}.");

    return true;
  }

  /**
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function builds()
  {
    return $this->hasMany('App\Build', 'projectid');
  }

  /**
   * @param Collection $build_collection
   * @throws \Exception
   */
  public function addBuilds(Collection $build_collection)
  {
    foreach ($build_collection as $build) {
      $this->addBuild($build);
    }
  }

  /**
   * @param Build $build
   * @return Model
   * @throws \Exception
   */
  public function addBuild(Build $build)
  {
    $message = '';
    if (!$this->isAtBuildCapacity($message)) {
      return $this->builds()->save($build);
    }
    throw new \Exception($message);
  }

  /**
   * @return bool
   */
  public function hasUnlimitedBuilds()
  {
    // Thinking maybe this stuff could go in a constructor and this could be a field
    // on the object rather than an instance method. (?) Just not sure if configuration
    // info like this belongs in Model objects, though it is not by any means difficult
    // to test, so maybe it stays. (?)
    $max_builds = Config::get('cdash.projects.max_builds');
    $unlimited_builds = Config::get('cdash.projects.with_unlimited_builds');
    return $max_builds == 0 || in_array($this->name, $unlimited_builds);
  }

  public function users()
  {
    $this->belongsToMany(
      'App\Project',
      'user2project',
      'userid',
      'projectid'
    );
  }
}
