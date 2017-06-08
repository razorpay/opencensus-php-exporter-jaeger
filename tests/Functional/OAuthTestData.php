<?php

return [
    'testGetAuthCode' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/authorize',
            'content' => ['response_type' => 'code',
                          'client_id'     => 'fj499348wn37hf',
                          'redirect_uri'  => 'http://www.example.com',
                          'scope'         => 'read_only'
                         ]
        ],
        'response' => [
            'content' => [
                'code' => 'something',
            ],
            'status_code' => 200
        ]
    ],

    'testPostAuthCode' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/authorize',
            'content' => ['response_type' => 'code',
                          // 'client_id'     => '7xGVN3iakS1EL2',
                          'redirect_uri'  => 'https://www.example.com',
                          'scope'         => 'read_only',
                          'user'          => json_encode(['authorize' => true,
                                              'name'      => 'test',
                                              'email'     => 'test@razorpay.com',
                                              'id'        => 'jfme94j9fr1234'
                                             ])
                         ]
        ],
        'response' => [
            'content' => [
                'code' => 'something',
            ],
            'status_code' => 200
        ]
    ],

    'getAccessTokenTest' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/token',
            'content' => ['grant_type'    => 'authorization_code',
                          // 'client_id'     => '7xGVN3iakS1EL2',
                          'client_secret' => 'supersecuresecret',
                          'redirect_uri'  => 'https://www.example.com'
                         ]
        ],
        'response' => [
            'content' => [
                'code' => 'something',
            ],
            'status_code' => 200
        ]
    ]
];