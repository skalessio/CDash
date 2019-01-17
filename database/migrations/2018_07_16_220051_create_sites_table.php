<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable(false)->default('');
            $table->string('ip')->nullable(false)->default('');
            $table->string('latitude', 10)->nullable(false)->default('');
            $table->string('longitude', 10)->nullable(false)->default('');
            $table->tinyInteger('outoforder')->nullable(false)->default('0');
            $table->unique(['name', 'ip']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sites');
    }
}
