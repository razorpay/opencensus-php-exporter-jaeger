<?php

$app->get('/', [
    'as'   => 'get_root',
    'middleware' =>'auth.hypertrace',
    'uses' => 'AuthController@getRoot'
]);

$app->get('/status', [
    'as'   => 'get_status',
    'uses' => 'AuthController@getStatus'
]);

$app->get('/authorize', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'get_auth_code',
    'uses' => 'AuthController@getAuthorize'
]);

$app->post('/authorize', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'post_auth_code',
    'uses' => 'AuthController@postAuthorize'
]);

$app->delete('/authorize', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'delete_auth_code',
    'uses' => 'AuthController@deleteAuthorize'
]);

$app->post('/token', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'post_access_token',
    'uses' => 'AuthController@postAccessToken'
]);

$app->post('/applications', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'create_application',
    'uses'       => 'ApplicationController@create'
]);

$app->get('/applications/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_application',
    'uses'       => 'ApplicationController@get'
]);

$app->get('/applications', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_multiple_applications',
    'uses'       => 'ApplicationController@getMultiple'
]);

$app->put('/applications/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'delete_application',
    'uses'       => 'ApplicationController@delete'
]);

$app->patch('/applications/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'update_application',
    'uses'       => 'ApplicationController@update'
]);

$app->post('/clients', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'create_application_clients',
    'uses'       => 'ClientController@createClients'
]);

$app->delete('/clients/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'delete_application_client',
    'uses'       => 'ClientController@delete'
]);

$app->get('/tokens/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_token',
    'uses'       => 'TokenController@get'
]);

$app->get('/public_tokens/{id}/validate', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'validate_public_token',
    'uses'       => 'TokenController@validatePublicToken'
]);

$app->get('/tokens', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_multiple_tokens',
    'uses'       => 'TokenController@getAll'
]);

$app->put('/tokens/{id}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'delete_token',
    'uses'       => 'TokenController@revoke'
]);

$app->post('/tokens/partner', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'create_token_partner',
    'uses'       => 'TokenController@createForPartner'
]);

$app->post('/tokens/internal', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'create_partner_token',
    'uses'       => 'AuthController@createPartnerToken'
]);

$app->get('/admin/entities/{entityType}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_admin_entities',
    'uses'       => 'AdminController@fetchMultipleForAdmin'
]);

$app->get('/admin/entities/{entityType}/{entityId}', [
    'middleware' => ['auth.api','auth.hypertrace'],
    'as'         => 'get_admin_entities',
    'uses'       => 'AdminController@fetchByIdForAdmin'
]);

$app->post('/authorize/tally', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'post_native_auth_code',
    'uses' => 'AuthController@postTallyAuthorize'
]);

$app->post('/tokens/tally', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'create_native_token',
    'uses' => 'AuthController@createTallyToken'
]);

$app->get('/authorize-multi-token', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'get_auth_code_multi_token',
    'uses' => 'AuthController@getAuthorizeMultiToken'
]);

$app->post('/authorize-multi-token', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'post_auth_code_multi_token',
    'uses' => 'AuthController@postAuthorizeMultiToken'
]);

$app->delete('/authorize-multi-token', [
    'middleware' =>'auth.hypertrace',
    'as'   => 'delete_auth_code_multi_token',
    'uses' => 'AuthController@deleteAuthorizeMultiToken'
]);

$app->post('/revoke', [
    'as'         => 'remove_access_token',
    'uses'       => 'TokenController@revokeByPartner'
]);
