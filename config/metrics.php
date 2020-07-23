<?php

use App\Constants\Metric;
use App\Trace\Metrics\DimensionsProcessor;

return [

    'processors' => [
        DimensionsProcessor::class,
    ],

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
    'namespace'  => 'auth_service',

    'whitelisted_label_values' => [],
    'default_label_value' => Metric::LABEL_DEFAULT_VALUE,

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
