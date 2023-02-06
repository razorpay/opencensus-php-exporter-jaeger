<?php

return [
    'testCreateApplication' => [
        'request'  => [
            'url'     => '/applications',
            'method'  => 'POST',
            'content' => [
                'name'        => 'app',
                'website'     => 'https://www.example.com',
                'logo_url'    => '/logo/app_logo.png',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'app',
                'website'     => 'https://www.example.com',
                'logo_url'    => '/logo/app_logo.png'
            ]
        ]
    ],
];
