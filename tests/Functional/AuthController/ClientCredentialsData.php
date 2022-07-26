<?php


return [
    'testPostAccessToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'client_credentials',
            ]
        ],
        'response' => [
            'content'     => [
                'token_type' => 'Bearer',
                'razorpay_account_id' => 'acc_10000000000000'
            ],
            'status_code' => 200
        ]
    ],

    'testPostAccessTokenWithInvalidClientID' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => 'invalidClient',
                'grant_type'   => 'client_credentials',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'No records found with the given Id'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\DBQueryException',
            'message' => 'No records found with the given Id',
        ],
    ],

    'testPostAccessTokenWithInvalidClientSecret' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'client_secret' => 'iuA=~#h_BMX9!wa<ZLs{^w{G',
                'grant_type'   => 'client_credentials',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Access denied'
                ],
            ],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Access denied',
        ]
    ],

    'testPostAccessTokenWithInvalidMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'mode' => 'te1',
                'grant_type'   => 'client_credentials',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Token provided is not valid'
                ],
            ],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Token provided is not valid',
        ]
    ],

    'testPostAccessTokenWithInvalidScope' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'client_credentials',
                'scope' => 'invalidScope'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'An unknown error occurred'
                ],
            ],
            'status_code' => 500
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\ServerException',
            'message' => 'An unknown error occurred',
        ]
    ],
];

