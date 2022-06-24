<?php

return [

    'testRefreshClients' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'apptest',
                'website'     => 'https://www.example.com',
            ]
        ]
    ],

    'testRefreshClientsInvalidInput' => [
        'request'   => [
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The application id field is required.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The application id field is required.',
        ],
    ],

];
