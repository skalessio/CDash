<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BuildGroup extends Model
{
  public $table = 'buildgroup';

  public $timestamps = false;

  protected $guarded = ['id'];

  public function project()
  {
    return $this->belongsTo('App\Project');
  }
}
