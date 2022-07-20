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

    'testGetAuthorizeUrl' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize?response_type=code&client_id=86KC3q506ytUPA&redirect_uri=http://localhost&scope=read_only&state=123'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAuthorizeUrlNoStateParam' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize?response_type=code&client_id=86KC3q506ytUPA&redirect_uri=http://localhost&scope=read_only'
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
            'content' => [
                'client_id'   => '30000000000000',
                'token'       => 'success',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPostAuthCodeES256' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => [
                'client_id'   => '30000000000000',
                'token'       => 'success',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPostAuthCodeWithWrongResponseType' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => [
                'token'       => 'incorrect_response_type',
                'client_id'   => '30000000000000',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Bad Request'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Bad Request',
        ],
    ],

    'testPostAuthCodeInvalidToken' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/authorize',
            'content' => [
                'token'       => 'invalid',
                'client_id'   => '30000000000000',
                'merchant_id' => '10000000000000',
            ]
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
            'content' => [
                'token'       => 'success',
                'merchant_id' => '10000000000000',
            ]
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

    'testPostAuthCodeInvalidRole' => [
        'request'   => [
            'method'  => 'delete',
            'url'     => '/authorize',
            'content' => [
                'token'       => 'invalid_role',
                'client_id'   => '30000000000000',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'The current user profile is restricted from this action'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'App\Exception\BadRequestException',
            'message' => 'The current user profile is restricted from this action',
        ],
    ],

    'testPostAccessToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'http://localhost',
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

    'testPostRefreshToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'refresh_token',
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

    'testPostAccessTokenForES256' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'https://www.example.com',
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

    'testPostRefreshTokenWithInvalidClientSecret' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'refresh_token',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'Access denied',
                ],
            ],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Access denied',
        ],
    ],

    'testPostRefreshTokenWithMissingRefreshToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'refresh_token',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'Access denied',
                ],
            ],
            'status_code' => 401
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Access denied',
        ],
    ],

    'testPostRefreshTokenWithMissingClientId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'https://www.example.com',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'Check the `client_id` parameter',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Check the `client_id` parameter',
        ],
    ],

    'testPostAccessTokenValidWrongRedirectUri' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'client_id'    => '30000000000000',
                'grant_type'   => 'authorization_code',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid redirect URI',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Invalid redirect URI',
        ],
    ],

    'testPostAuthCodeAndGenerateAccessToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/tokens/internal',
            'content' => [
                'merchant_id'         => '10000000000000',
                'user_id'             => '20000000000000',
                'redirect_uri'        => 'https://www.example.com',
                'partner_merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content'     => [
                'token_type' => 'Bearer',
            ],
            'status_code' => 200
        ]
    ],

    'testPostAuthCodeAndGenerateAccessTokenInvalidInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/tokens/internal',
            'content' => [
                'merchant_id'         => '10000000000000A',
                'user_id'             => '20000000000000',
                'redirect_uri'        => 'https://www.example.com',
                'partner_merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The merchant id must be 14 characters.'
                ],
            ],
            'status_code' => 500
        ],
        'exception' => [
            'class'   => 'Razorpay\Spine\Exception\ValidationFailureException',
            'message' => 'Validation failed. The merchant id must be 14 characters.',
        ],
    ],

    'testValidateTallyAuthUser' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/authorize/tally',
            'content' => [
                'merchant_id'         => '10000000000000',
                'login_id'            => 'test@razorpay.com',
            ]
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200
        ]
    ],

    'testValidateTallyAuthUserInvalidInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/authorize/tally',
            'content' => [
                'merchant_id'         => '10000000000000',
                'login_id'            => 'test@razorpay.com',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid client'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'App\Exception\BadRequestValidationFailureException',
            'message' => 'Invalid client',
        ],
    ],

    'testTallyToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/tokens/tally',
            'content' => [
                'merchant_id'         => '10000000000000',
                'grant_type'          => 'tally_client_credentials',
                'pin'                 => '0007',
                'login_id'            => 'test@razorpay.com',
            ]
        ],
        'response' => [
            'content'     => [
                'token_type' => 'Bearer',
            ],
            'status_code' => 200
        ]
    ],

    'testTallyTokenInvalidInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/tokens/tally',
            'content' => [
                'merchant_id'         => '10000000000000',
                'grant_type'          => 'tally_client_credentials_invalid',
                'pin'                 => '0007',
                'login_id'            => 'test@razorpay.com',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The selected grant type is invalid.'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'App\Exception\BadRequestValidationFailureException',
            'message' => 'Validation failed. The selected grant type is invalid.',
        ],
    ],

    'testPostAccessTokenWithInvalidGrant' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'grant_type'   => 'some_code',
                'redirect_uri' => 'https://www.example.com',
                'client_id'    => '30000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Check the `grant_type` parameter'
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Check the `grant_type` parameter',
        ],
    ],

    'testPostAccessTokenWithMissingCode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'https://www.example.com',
                'client_id'    => '30000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Check the `code` parameter',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\BadRequestException',
            'message' => 'Check the `code` parameter',
        ],
    ],

    'testPostAccessTokenWithIncorrectSecret' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/token',
            'content' => [
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'https://www.example.com',
                'client_id'    => '30000000000000',
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
            'content' => [
                'client_id'    => 'invalidClient',
                'grant_type'   => 'authorization_code',
                'redirect_uri' => 'https://www.example.com',
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
                'merchant_id'  => '10000000000000',
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
                'merchant_id'  => '10000000000000',
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
                'errors' => ['User data not found'],
            ],
        ],
    ]
];
