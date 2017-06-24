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
$app->get('/', [
	'as' => 'get_root',
	'uses' => 'AuthController@getRoot'
]);

$app->get('/authorize', [
	'as' => 'get_auth_code',
	'uses' => 'AuthController@getAuthorize'
]);

$app->post('/authorize', [
	'as' => 'post_auth_code',
	'uses' => 'AuthController@postAuthorize'
]);

$app->post('/token', [
	'as' => 'post_access_token',
	'uses' => 'AuthController@postAccessToken'
]);

$app->get('/logged_in', [
	'as' => 'get_logged_in',
	'uses' => 'AuthController@getLoggedIn'
]);
