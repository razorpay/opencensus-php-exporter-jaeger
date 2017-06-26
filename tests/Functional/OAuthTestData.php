<?php

return [
    'testPostAuthCode' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => ['response_type' => 'code',
                          'redirect_uri' => 'https://www.example.com',
                          'scope'        => 'read_only',
                          'user'         => json_encode(['authorize' => true,
                                              'name'  => 'test',
                                              'email' => 'test@razorpay.com',
                                              'id'    => 'jfme94j9fr1234'
                                             ])
                         ]
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPostAuthCodeWithReject' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => ['response_type' => 'code',
                          'redirect_uri' => 'https://www.example.com',
                          'scope'        => 'read_only',
                          'user'         => json_encode(['authorize' => false,
                                              'name'  => 'test',
                                              'email' => 'test@razorpay.com',
                                              'id'    => 'jfme94j9fr1234'
                                             ])
                         ]
        ],
        'response' => [
            'content' => ['error' => 'Missing argument or User denied access'],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Missing argument or User denied access',
        ],
    ],

    'testPostAccessToken' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'    => 'authorization_code',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => [
              'token_type' => 'Bearer',
            ],
            'status_code' => 200
        ]
    ],

    'testPostAccessTokenWithInvalidGrant' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'    => 'some_code',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => ['error' => 'Missing argument or incorrect value provided for response_type'],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Missing argument or incorrect value provided for response_type',
        ],
    ],

    'testPostAccessTokenWithMissingCode' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'    => 'authorization_code',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => ['error' => 'Invalid request, please check for missing arguments or incorrect values or arguments repeating.'],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Invalid request, please check for missing arguments or incorrect values or arguments repeating.',
        ],
    ],

    'testPostAccessTokenWithIncorrectSecret' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'    => 'authorization_code',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => ['error' => 'Access denied'],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Access denied',
        ],
    ],

    'testPostAccessTokenWithIncorrectClientId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => ['grant_type'    => 'authorization_code',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => ['error' => 'No records found with the given Id'],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\DBQueryException',
            'message' => 'No records found with the given Id',
        ],
    ],

    'testGetTokenData' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/success/token_data',
            'content' => [
                'id'          => '20000000000000',
                'merchant_id' => 'merchant_id',
                'role'        => 'owner',
                'user'        => [
                    'id'             => '20000000000000',
                    'name'           => 'fdfd',
                    'email'          => 'fdsfsd@dfsd.dsfd',
                    'contact_mobile' => '9999999999',
                    'created_at'     => '1497678977',
                    'updated_at'     => '1497678977',
                    'merchant_id'    => '10000000000000',
                    'confirmed'      => true
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'data'    => [
                    'id'          => '20000000000000',
                    'merchant_id' => 'merchant_id',
                    'role'        => 'owner',
                    'user'        => [
                        'id'             => '20000000000000',
                        'name'           => 'fdfd',
                        'email'          => 'fdsfsd@dfsd.dsfd',
                        'contact_mobile' => '9999999999',
                        'created_at'     => '1497678977',
                        'updated_at'     => '1497678977',
                        'merchant_id'    => '10000000000000',
                        'confirmed'      => true,
                    ],
                ],
            ],
        ]
    ],

    'testGetTokenDataWithInvalidToken' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/invalid/token_data',
            'content' => ['User data not found'],
        ],
        'response' => [
            'content' => [
                'success' => false,
                'errors' => ['User data not found'],
            ],
        ],
    ],

    'testGetRoot' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/'
        ],
        'response' => [
            'content' => [
                'message' => 'Welcome to Razorpay Auth!',
            ],
        ],
    ],
];