<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BuildUpdate extends Model
{
  /** @var string $table */
  protected $table = 'buildupdate';

  /** @var bool $timestamps */
  public $timestamps = false;

  public function builds()
  {
    return $this->belongsToMany(
      'App\Build',
      'build2update',
      'buildid',
      'updateid'
    );
  }

  public function buildUpdateFiles()
  {
    return $this->hasMany('App\BuildUpdateFile');
  }
}
