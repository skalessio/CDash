<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'firstname' => $faker->firstName,
        'lastname' => $faker->lastName,
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Project::class, function (Faker\Generator $faker) {
  return [
    'name' => $faker->name,
    'description' => $faker->sentence,
    'homeurl' => $faker->url,
    'cvsurl' => $faker->url,
    'bugtrackerurl' => $faker->url,
    'bugtrackerfileurl' => $faker->url,
    'bugtrackernewissueurl' => $faker->url,
    'documentationurl' => $faker->url,
    'emailbrokensubmission' => 1,
    'emailredundantfailures' => 0,
    'autoremovemaxbuilds' => 500,
    'autoremovetimeframe' => 60,
    'coveragethreshold' => 70,
    'cvsviewertype' => 'viewcvs',
    'emailmaxchars' => 255,
    'emailmaxitems' => 5,
    'nightlytime' => '01:00:00 UTC',
    'public' => 1,
    'showcoveragecode' => 1,
    'testtimemaxstatus' => 3,
    'testtimestd' => 4,
    'testtimestdthreshold' => 1,
    'uploadquota' => 1
  ];
});

$factory->define(App\Build::class, function (Faker\Generator $faker) {
  return [
    'name' => $faker->userAgent,
    'stamp' => $faker->unixTime,
    'starttime' => $faker->dateTime('-15 minutes'),
    'endtime' => $faker->dateTime('now'),
    'uuid' => $faker->uuid,
  ];
});

