<?php

$hostname = getenv('HOSTNAME', true) ?: "localhost" ;

return [
    /*
     * The following debug options come
     * into play only 'debug' is true.
     *
     * They define the places where the
     * logs will be written.
     */
    'debug_options' => [
        'screen'  => false,
        'browser' => false,
        'chrome'  => false
    ],

    'channel' => 'AUTH-SERVICE',

    'cache' => storage_path('framework/cache/'),

    'cloud' => ! env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Displays line/file/class/method from which the log call originated
    |--------------------------------------------------------------------------
    */
    'introspection' => true,

    /*
    |--------------------------------------------------------------------------
    | Path for trace logs
    |--------------------------------------------------------------------------
    */
    //'logpath' => storage_path('logs/trace.log'),
    'logpath' => storage_path().'/logs/'.$hostname.'-trace.log',

    'mockAws' => false,

    'fallback_email' => 'developers@razorpay.com',

    'trace_code_class' => App\Constants\TraceCode::class,
];
