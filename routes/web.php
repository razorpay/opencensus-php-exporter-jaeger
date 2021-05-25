<?php

$app->get('/', [
    'as'   => 'get_root',
    'uses' => 'AuthController@getRoot'
]);

$app->get('/status', [
    'as'   => 'get_status',
    'uses' => 'AuthController@getStatus'
]);

$app->get('/authorize', [
    'as'   => 'get_auth_code',
    'uses' => 'AuthController@getAuthorize'
]);

$app->post('/authorize', [
    'as'   => 'post_auth_code',
    'uses' => 'AuthController@postAuthorize'
]);

$app->delete('/authorize', [
    'as'   => 'delete_auth_code',
    'uses' => 'AuthController@deleteAuthorize'
]);

$app->post('/token', [
    'as'   => 'post_access_token',
    'uses' => 'AuthController@postAccessToken'
]);

$app->post('/applications', [
    'middleware' => 'auth.api',
    'as'         => 'create_application',
    'uses'       => 'ApplicationController@create'
]);

$app->get('/applications/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'get_application',
    'uses'       => 'ApplicationController@get'
]);

$app->get('/applications', [
    'middleware' => 'auth.api',
    'as'         => 'get_multiple_applications',
    'uses'       => 'ApplicationController@getMultiple'
]);

$app->put('/applications/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'delete_application',
    'uses'       => 'ApplicationController@delete'
]);

$app->patch('/applications/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'update_application',
    'uses'       => 'ApplicationController@update'
]);

$app->post('/clients', [
    'middleware' => 'auth.api',
    'as'         => 'create_application_clients',
    'uses'       => 'ClientController@createClients'
]);

$app->delete('/clients/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'delete_application_client',
    'uses'       => 'ClientController@delete'
]);

$app->get('/tokens/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'get_token',
    'uses'       => 'TokenController@get'
]);

$app->get('/tokens', [
    'middleware' => 'auth.api',
    'as'         => 'get_multiple_tokens',
    'uses'       => 'TokenController@getAll'
]);

$app->put('/tokens/{id}', [
    'middleware' => 'auth.api',
    'as'         => 'delete_token',
    'uses'       => 'TokenController@revoke'
]);

$app->post('/tokens/partner', [
    'middleware' => 'auth.api',
    'as'         => 'create_token_partner',
    'uses'       => 'TokenController@createForPartner'
]);

$app->post('/tokens/internal', [
    'middleware' => 'auth.api',
    'as'         => 'create_partner_token',
    'uses'       => 'AuthController@createPartnerToken'
]);

$app->get('/admin/entities/{entityType}', [
    'middleware' => 'auth.api',
    'as'         => 'get_admin_entities',
    'uses'       => 'AdminController@fetchMultipleForAdmin'
]);

$app->get('/admin/entities/{entityType}/{entityId}', [
    'middleware' => 'auth.api',
    'as'         => 'get_admin_entities',
    'uses'       => 'AdminController@fetchByIdForAdmin'
]);

$app->post('/authorize/tally', [
    'as'   => 'post_native_auth_code',
    'uses' => 'AuthController@postTallyAuthorize'
]);

$app->post('/tokens/tally', [
    'as'   => 'create_native_token',
    'uses' => 'AuthController@createTallyToken'
]);
