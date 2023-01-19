<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'auth' => [
            'driver'    => env('DB_DRIVER'),
            'host'      => env('DB_HOST'),
            'port'      => env('DB_PORT'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            // Ref- https://github.com/laravel/framework/pull/24038/files.
            // Once laravel/lumen-framework is upgraded this(below) will no longer be required.
            'modes'     => [
                'ONLY_FULL_GROUP_BY',
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_ENGINE_SUBSTITUTION',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        // This connection is used by Services/SignerCache.php
        'signer_cache' => [
            'host'               => env('SIGNER_CACHE_HOST'),
            'port'               => env('SIGNER_CACHE_PORT'),
            'username'           => env('SIGNER_CACHE_USERNAME'),
            'password'           => env('SIGNER_CACHE_PASSWORD'),
            'scheme'             => env('SIGNER_CACHE_SCHEME'),

            // Difference between timeout and read_write timeout: https://squizzle.me/php/predis/doc/Configuration
            'timeout'            => env('SIGNER_CACHE_CONN_TIMEOUT_SECS'),
            'read_write_timeout' => env('SIGNER_CACHE_RW_TIMEOUT_SECS'),

            // When `persistent` is set to true, it means connection will not be closed until PHP process dies
            'persistent'         => true,
            'ssl'                => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
                'crypto_type'      => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            ]
        ],
    ],
];
