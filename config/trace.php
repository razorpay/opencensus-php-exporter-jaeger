<?php

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

    'cacheDir' => storage_path('framework/cache/'),

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
    'logpath' => storage_path('logs/trace.log'),

    'mockAws' => false,

    'fallbackEmail' => 'developers@razorpay.com'
];