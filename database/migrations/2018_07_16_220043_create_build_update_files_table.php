<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildUpdateFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('build_update_file', function (Blueprint $table) {
            // $table->increments('id');
          $table->integer('updateid')->nullable(false)->default('0');
          $table->string('filename')->nullable(false)->default('');
          $table->dateTime('checkindate')->nullable(false)->default('1980-01-01 00:00:00');
          $table->string('author')->nullable(false)->default('');
          $table->string('email')->nullable(false)->default('');
          $table->string('committer')->nullable(false)->default('');
          $table->string('committeremail')->nullable(false)->default('');
          $table->text('log')->nullable(false);
          $table->string('revision', 60)->nullable(false)->default('0');
          $table->string('priorrevision', 60)->nullable(false)->default('0');
          $table->string('status', 12)->nullable(false)->default('');
          $table->index('updateid');
          $table->index('author');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('build_update_files');
    }
}
