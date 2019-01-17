<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('build_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable(false)->default('');
            $table->integer('projectid')->nullable(false)->default('0');
            $table->timestamp('starttime')->nullable(false)->default('1980-01-01 00:00:00');
            $table->timestamp('endtime')->nullable(false)->default('1980-01-01 00:00:00');
            $table->string('autoremovetimeframe')->default('0');
            $table->text('description')->nullable(false)->default('');
            $table->boolean('summaryemail')->default('0');
            $table->boolean('includesubprojecttotal')->default('1');
            $table->boolean('emailcommitters')->default('0');
            $table->string('type', 20)->nullable(false)->default('Daily');
            $table->index('projectid');
            $table->index('starttime');
            $table->index('endtime');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('build_groups');
    }
}
