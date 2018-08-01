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

    'testCreateApplicationInvalidSecret' => [
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
            'content'     => [
                'error' => 'Unauthorized'
            ],
            'status_code' => 401,
        ],
    ],

    'testCreateApplicationMissingInput' => [
        'request'   => [
            'url'     => '/applications',
            'method'  => 'POST',
            'content' => [
                'website'     => 'https://www.example.com',
                'logo_url'    => '/logo/app_logo.png',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The name field is required.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The name field is required.',
        ],
    ],

    'testCreateApplicationInvalidInput' => [
        'request'   => [
            'url'     => '/applications',
            'method'  => 'POST',
            'content' => [
                'name'        => 'dfdfd',
                'website'     => 'www.example.com',
                'logo_url'    => '/logo/app_logo.png',
                'merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The website format is invalid.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The website format is invalid.',
        ],
    ],

    'testGetApplication' => [
        'request'  => [
            'method'  => 'GET',
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

    'testGetMissingApplication' => [
        'request'   => [
            'url'     => '/applications/dnk3ere',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'No records found with the given Id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'   => Razorpay\OAuth\Exception\DBQueryException::class,
            'message' => 'No records found with the given Id',
        ],
    ],

    'testGetApplications' => [
        'request'  => [
            'url'     => 'applications',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetApplicationsByType' => [
        'request'  => [
            'url'     => 'applications',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
                'type'        => 'partner',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testUpdateApplication' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'apptestnew',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'apptestnew',
                'website'     => 'https://www.example.com',
            ]
        ]
    ],

    'testUpdateApplicationInvalidInput' => [
        'request'   => [
            'method'  => 'PATCH',
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'apptestnew',
                'website'     => 'kfdsfs',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Validation failed. The website format is invalid.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The website format is invalid.',
        ],
    ],

    'testDeleteApplication' => [
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
];
