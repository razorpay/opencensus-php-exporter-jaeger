<?php

return [

    'segment_analytics' => [
        'url'                => env('SEGMENT_ANALYTICS_URL'),
        'mock'               => env('SEGMENT_ANALYTICS_MOCK', false),
        'request_timeout'    => env('SEGMENT_ANALYTICS_TIMEOUT', 500),
        'connection_timeout' => env('SEGMENT_ANALYTICS_CONNECTION_TIMEOUT', 500),
        'auth' => [
            'write_key'       => env('SEGMENT_ANALYTICS_WRITE_KEY'),
        ],
    ]
];
