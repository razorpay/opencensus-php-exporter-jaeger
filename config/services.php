<?php

return [

    'segment_analytics' => [
        'url'                => env('SEGMENT_ANALYTICS_URL'),
        'mock'               => env('SEGMENT_ANALYTICS_MOCK', false),
        'request_timeout'    => env('SEGMENT_ANALYTICS_TIMEOUT', 500),
        'connection_timeout' => env('SEGMENT_ANALYTICS_CONNECTION_TIMEOUT', 500),
        'auth' => [
            'write_key'      => env('SEGMENT_ANALYTICS_WRITE_KEY'),
        ],
    ],

    'splitz' => [
        'url'                => env('SPLITZ_URL'),
        'username'           => 'auth',
        'secret'             => env('SPLITZ_SECRET'),
        'request_timeout'    => env('SPLITZ_REQUEST_TIMEOUT', 0.1),
        'enabled'            => env('SPLITZ_ENABLED', true),
        'mock'               => env('SPLITZ_MOCK', false)
    ],

    'dcs' => [
        'mock'          => env('DCS_MOCK', false),
        'live'          => [
            "url"           => env('DCS_LIVE_URL'),
            'username'      => 'auth',
            'password'      => env('DCS_AUTH_PASSWORD_LIVE'),
        ],
        'test'          => [
            "url"           => env('DCS_TEST_URL'),
            'username'      => 'auth',
            'password'      => env('DCS_AUTH_PASSWORD_TEST'),
        ],
    ],
];
