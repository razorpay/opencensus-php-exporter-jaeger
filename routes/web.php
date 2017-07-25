<?php

$app->get('/', [
	'as' => 'get_root',
	'uses' => 'AuthController@getRoot'
]);

$app->get('/status', [
    'as' => 'get_status',
    'uses' => 'AuthController@getStatus'
]);

$app->get('/authorize', [
	'as' => 'get_auth_code',
	'uses' => 'AuthController@getAuthorize'
]);

$app->post('/authorize', [
	'as' => 'post_auth_code',
	'uses' => 'AuthController@postAuthorize'
]);

$app->delete('/authorize', [
	'as' => 'delete_auth_code',
	'uses' => 'AuthController@deleteAuthorize'
]);

$app->post('/token', [
	'as' => 'post_access_token',
	'uses' => 'AuthController@postAccessToken'
]);
