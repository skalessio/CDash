<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildUpdateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('build_update', function (Blueprint $table) {
          $table->increments('id');
          $table->timestamp('starttime')->nullable(false)->default('1980-01-01 00:00:00');
          $table->timestamp('endtime')->nullable(false)->default('1980-01-01 00:00:00');
          $table->text('command')->nullable(false);
          $table->string('type', 4)->nullable(false)->default('');
          $table->text('status')->nullable(false);
          $table->smallIncrements('nfiles')->default('-1');
          $table->smallIncrements('warnings')->default('-1');
          $table->string('revision', 60)->nullable(false)->default('0');
          $table->string('priorrevision', 60)->nullable(false)->default('0');
          $table->string('path')->nullable(false)->default('');
          $table->index('revision');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('build_updates');
    }
}
