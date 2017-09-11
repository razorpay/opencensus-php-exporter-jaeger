<?php

return [
    'testGetRoot' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/'
        ],
        'response' => [
            'content' => [
                'message' => 'Welcome to Razorpay Auth!',
            ],
        ],
    ],

    'testGetStatus' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/status'
        ],
        'response' => [
            'content' => [
                'DB' => 'Ok',
            ],
        ],
    ],

    'testPostAuthCode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => ['token' => 'success']
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPostAuthCodeWithWrongResponseType' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => ['token' => 'incorrect_response_type']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Missing argument or incorrect value provided for response_type'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Missing argument or incorrect value provided for response_type',
        ],
    ],

    'testPostAuthCodeInvalidToken' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => ['token' => 'invalid']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'There was a problem with the application you are trying to connect to, please contact the application provider for support.'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'App\Exception\BadRequestException',
            'message' => 'There was a problem with the application you are trying to connect to, please contact the application provider for support.',
        ],
    ],

    'testPostAuthCodeWithReject' => [
        'request'   => [
            'method'  => 'delete',
            'url'     => '/authorize',
            'content' => ['token' => 'success']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Missing argument or User denied access'
                ],
            ],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Missing argument or User denied access',
        ],
    ],

    'testPostAccessToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'   => 'authorization_code',
                          'redirect_uri' => 'https://www.example.com'
            ]
        ],
        'response' => [
            'content'     => [
                'token_type' => 'Bearer',
            ],
            'status_code' => 200
        ]
    ],

    'testPostAccessTokenWithInvalidGrant' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'   => 'some_code',
                          'redirect_uri' => 'https://www.example.com'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Missing argument or incorrect value provided for response_type'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Missing argument or incorrect value provided for response_type',
        ],
    ],

    'testPostAccessTokenWithMissingCode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'   => 'authorization_code',
                          'redirect_uri' => 'https://www.example.com'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid request, please check for missing arguments or incorrect values or arguments repeating.'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Invalid request, please check for missing arguments or incorrect values or arguments repeating.',
        ],
    ],

    'testPostAccessTokenWithIncorrectSecret' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'   => 'authorization_code',
                          'redirect_uri' => 'https://www.example.com'
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
        ],
    ],

    'testPostAccessTokenWithIncorrectClientId' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'   => 'authorization_code',
                          'redirect_uri' => 'https://www.example.com'
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

    'testGetTokenData' => [
        'request' => [
            'content' => [
                'user_id'      => '20000000000000',
                'user_email'   => 'test@razorpay.com',
                'merchant_id'  => 'merchant_id',
                'role'         => 'owner',
                'user'         => [
                    'id'             => '20000000000000',
                    'name'           => 'fdfd',
                    'email'          => 'fdsfsd@dfsd.dsfd',
                    'contact_mobile' => '9999999999',
                    'created_at'     => '1497678977',
                    'updated_at'     => '1497678977',
                    'merchant_id'    => '10000000000000',
                    'confirmed'      => true
                ],
                'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=code&amp;scope=read_only'
            ],
        ],
    ],

    'testGetTokenDataWrongResponseType' => [
        'request' => [
            'content' => [
                'user_id'      => '20000000000000',
                'user_email'   => 'test@razorpay.com',
                'merchant_id'  => 'merchant_id',
                'role'         => 'owner',
                'user'         => [
                    'id'             => '20000000000000',
                    'name'           => 'fdfd',
                    'email'          => 'fdsfsd@dfsd.dsfd',
                    'contact_mobile' => '9999999999',
                    'created_at'     => '1497678977',
                    'updated_at'     => '1497678977',
                    'merchant_id'    => '10000000000000',
                    'confirmed'      => true
                ],
                'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=invalid&amp;scope=read_only'
            ],
        ]
    ],

    'testGetTokenDataWithInvalidToken' => [
        'request'  => [
            'content' => ['User data not found'],
        ],
        'response' => [
            'content' => [
                'success' => false,
                'errors'  => ['User data not found'],
            ],
        ],
    ],
];
