<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Build extends Model
{
  protected $table = 'build';
  public $timestamps = false;

  public function project()
  {
    return $this->belongsTo('App\Project');
  }

  public function buildUpdate()
  {
    return $this->belongsToMany(
      'App/BuildUpdate',
      'build2update',
      'updateid',
      'buildid'
    );
  }
}
