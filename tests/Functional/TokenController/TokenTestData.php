<?php

return [
    'testGetToken' => [
        'request' => [
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'user_id'     => '20000000000000',
                'type'        => 'access_token',
            ]
        ]
    ],

    'testGetMissingToken' => [
        'request' => [
            'url'     => '/tokens/fsdfdsf',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'DB Query Failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'   => Razorpay\OAuth\Exception\DBQueryException::class,
            'message' => 'DB Query Failed',
        ],
    ],

    'testGetTokens' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'merchant_id'  => '10000000000000',
                'redirect_url' => ['https://www.example.com'],
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'  => '10000000000000',
                'environment'  => 'prod',
                'redirect_url' => ['https://www.example.com'],
            ]
        ]
    ],

    'testGetAllTokens' => [
        'request'  => [
            'url'     =>'tokens',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteToken' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRevokeAccessTokenByPartner' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'client_id'    => '30000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRevokeAccessTokenForMobileApp' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'client_id'    => '30000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRevokeRefreshTokenByPartner' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'client_id'    => '30000000000000',
            ]
        ],
        'response' => [
            'content' => [],
            'status_code'=>400,
        ]
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
            'content' => []
        ]
    ],

    'testValidatePublicTokenWithValidToken' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => 'public_tokens/%s/validate',
            'content' => []
        ],
        'response' => [
            'content' => [
                'exist' => true
            ]
        ]
    ],

    'testValidatePublicTokenWithInvalidToken' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => 'public_tokens/rzp_live_oauth_Jmx3lfYzyhtJap/validate',
            'content' => []
        ],
        'response' => [
            'content' => [
                'exist' => false
            ]
        ]
    ]
];
