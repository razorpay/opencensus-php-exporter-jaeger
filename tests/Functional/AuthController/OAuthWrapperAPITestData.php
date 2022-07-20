<?php

return [
    'testGetAuthorizeMultiTokenUrlWithInvalidClientId' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize-multi-token?response_type=code&live_client_id=40000000000000&test_client_id=30000000000000&redirect_uri=http://localhost&scope=read_only&state=123'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAuthorizeMultiTokenUrlWithInvalidTestClientId' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize-multi-token?response_type=code&live_client_id=86KC3q506ytUPA&test_client_id=34KC3q534ytADN&redirect_uri=http://localhost&scope=read_only&state=123'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAuthorizeMultiTokenUrl' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize-multi-token?response_type=code&live_client_id=86KC3q506ytUPA&test_client_id=34KC3q534ytADN&redirect_uri=http://localhost&scope=read_only&state=123'
        ],
        'response' => [
            'content' => [
                'DB' => 'Ok',
            ],
        ],
    ],

    'testGetAuthorizeMultiTokenUrlWithNoStateParam' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/authorize-multi-token?response_type=code&client_id=86KC3q506ytUPA&redirect_uri=http://localhost&scope=read_only'
        ],
        'response' => [
            'content' => [
                'DB' => 'Ok',
            ],
        ],
    ],

    'testPostAuthCodeMultiToken' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/authorize-multi-token',
            'content' => [
                'live_client_id'   => '40000000000000',
                'test_client_id'   => '30000000000000',
                'token'       => 'multi_token_success',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPostAuthCodeMultiTokenWithInvalidToken' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/authorize-multi-token',
            'content' => [
                'token'       => 'multi_token_invalid',
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

    'testPostAuthCodeMultiTokenWithReject' => [
        'request'   => [
            'method'  => 'delete',
            'url'     => '/authorize-multi-token',
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

    'testPostAuthCodeMultiTokenWithInvalidRole' => [
        'request'   => [
            'method'  => 'delete',
            'url'     => '/authorize-multi-token',
            'content' => [
                'token'       => 'multi_token_invalid_role',
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

    'testPostAuthCodeMultiTokenWithWrongResponseType' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/authorize-multi-token',
            'content' => [
                'token'       => 'multi_token_incorrect_response_type',
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
    ]
];
