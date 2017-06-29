<?php

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

//better name and url format
$app->get('/{token}/token_data', [
	'as' => 'get_user_detail',
	'uses' => 'AuthController@getTokenData'
]);
