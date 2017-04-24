<?php

use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/authorize', [
	'as' => 'get_auth_code',
	'uses' => 'AuthController@authorize'
]);

$app->get('/get_access', [
	'as' => 'get_access_token',
	'uses' => 'AuthController@getAccessToken'
]);
