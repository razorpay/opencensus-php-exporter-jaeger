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

//post authorize for actually getting the auth code on grant accept

$app->post('/token', [
	'as' => 'post_access_token',
	'uses' => 'AuthController@postAccessToken'
]);

//better name and url format
$app->get('/{token}/token_data', [
	'as' => 'get_user_detail',
	'uses' => 'AuthController@getTokenData'
]);
