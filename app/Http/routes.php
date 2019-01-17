<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
Route::auth();

Route::any('/submit', 'CdashController@submit');

// This route must be the last route to check for deprecated requests
// to CDash, if the request does tno exist it will return a 404
Route::any('{url}', 'CdashController@cdash')->where('url', '.*');

Route::get('/home', 'HomeController@index');
