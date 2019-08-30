<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    | Possible values: mock, dogstatsd
    |--------------------------------------------------------------------------
    |
    */
    'default'    => env('METRICS_DEFAULT_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Metrics namespace: Used as prefix to metric names
    |--------------------------------------------------------------------------
    |
    */
    'namespace'  => 'api',

    /*
    |--------------------------------------------------------------------------
    | Configurations per driver
    |--------------------------------------------------------------------------
    |
    */
    'drivers'    => [
        'mock' => [
            'impl' => \Razorpay\Metrics\Drivers\Mock::class,
        ],

        'dogstatsd' => [
            'impl'   => \Razorpay\Metrics\Drivers\Dogstatsd::class,
            'client' => [
                'host' => env('METRICS_DOGSTATSD_HOST'),
                'port' => env('METRICS_DOGSTATSD_PORT'),
            ],
        ],
    ],
];
