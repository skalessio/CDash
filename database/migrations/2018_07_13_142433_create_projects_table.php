<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable(false)->default('');
            $table->text('description')->nullable(false)->default('');
            $table->string('homeurl')->nullable(false)->default('');
            $table->string('cvsurl')->nullable(false)->default('');
            $table->string('bugtrackerurl')->nullable(false)->default('');
            $table->string('bugtrackerfileurl')->nullable(false)->default('');
            $table->string('bugtrackernewissueurl')->nullable(false)->default('');
            $table->string('bugtrackertype', 16)->nullable(false)->default('');
            $table->string('documentationurl')->nullable(false)->default('');
            $table->integer('imageid')->nullable(false)->default('0');
            $table->boolean('public')->nullable(false)->default('1');
            $table->unsignedSmallInteger('coveragethreshold')->nullable(false)->default('70');
            $table->string('testingdataurl')->nullable(false)->default('');
            $table->string('nightlytime', 50)->nullable(false)->default('00:00:00');
            $table->string('googletracker', 50)->nullable(false)->default('');
            $table->boolean('emaillowcoverage')->nullable(false)->default('0');
            $table->boolean('emailtesttimingchanged')->nullable(false)->default('0');
            $table->boolean('emailbrokensubmission')->nullable(false)->default('1');
            $table->boolean('emailredundantfailures')->nullable(false)->default('0');
            $table->boolean('emailadministrator')->nullable(false)->default('1');
            $table->boolean('showipaddresses')->nullable(false)->default('1');
            $table->string('cvsviewertype', 10)->nullable();
            $table->float('testtimestd', 3, 1)->default('4.0');
            $table->float('testtimestdthreshold', 3, 1)->default('1.0');
            $table->boolean('showtesttime')->default('0');
            $table->tinyInteger('testtimemaxstatus')->default('3');
            $table->tinyInteger('emailmaxitems')->default('5');
            $table->integer('emailmaxchars')->default('255');
            $table->boolean('displaylabels')->default('1');
            $table->integer('autoremovetimeframe')->default('0');
            $table->integer('autoremovemaxbuilds')->default('300');
            $table->bigInteger('uploadquota')->default('0');
            $table->string('webapikey', 40);
            $table->integer('tokenduration');
            $table->boolean('showcoveragecode')->default('1');
            $table->boolean('sharelabelfilters')->default('0');
            $table->boolean('authenticatesubmissions')->default('0');

            // indexes
            $table->index('name');
            $table->index('public');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('projects');
    }
}
