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
                    'description' => 'Validation failed. The website must be a valid URL.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The website must be a valid URL.',
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
                    'description' => 'Validation failed. The website must be a valid URL.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => Razorpay\Spine\Exception\ValidationFailureException::class,
            'message' => 'Validation failed. The website must be a valid URL.',
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

    'testGetSubmerchantApplications' => [
        'request'  => [
            'url'     => '/applications/submerchant',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'application_name' => 'apptest',
                        'scopes'    => [
                            [
                                'scope'         => 'read_only',
                                'description'   => 'apptest can view payments, view refunds, view disputes & settlements'
                            ]
                        ],
                        'access_granted_at' => 1562400123,
                        'logo_url' => '/logos/8f6s8096pYQw0v.png'
                    ]
                ]
            ]
        ]
    ],

    'testGetSubmerchantApplicationsWithVaryingScopesAndCreationTime' => [
        'request'  => [
            'url'     => '/applications/submerchant',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'application_name' => 'apptest',
                        'scopes'    => [
                            [
                                'scope'         => 'rx_read_only',
                                'description'   => 'apptest has read-only access to all the RazorpayX resources'
                            ],
                            [
                                'scope'         => 'read_write',
                                'description'   => 'apptest can create & view payments, create & view refunds, view disputes & settlements'
                            ]
                        ],
                        'access_granted_at' => 1562400120,
                        'logo_url' => '/logos/8f6s8096pYQw0v.png'
                    ]
                ]
            ]
        ]
    ],

    'testGetMultipleSubmerchantApplications' => [
        'request'  => [
            'url'     => '/applications/submerchant',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'application_name' => 'apptestSecond',
                        'scopes'    => [
                            [
                                'scope'         => 'rx_read_only',
                                'description'   => 'apptestSecond has read-only access to all the RazorpayX resources'
                            ],
                            [
                                'scope'         => 'read_write',
                                'description'   => 'apptestSecond can create & view payments, create & view refunds, view disputes & settlements'
                            ]
                        ],
                        'access_granted_at' => 1562400124,
                        'logo_url' => '/logos/8f6s8096pYQw0g.png'
                    ],
                    [
                        'application_name' => 'apptestFirst',
                        'scopes'    => [
                            [
                                'scope'         => 'read_write',
                                'description'   => 'apptestFirst can create & view payments, create & view refunds, view disputes & settlements'
                            ],
                            [
                                'scope'         => 'read_only',
                                'description'   => 'apptestFirst can view payments, view refunds, view disputes & settlements'
                            ]
                        ],
                        'access_granted_at' => 1562400120,
                        'logo_url' => '/logos/8f6s8096pYQw0v.png'
                    ]
                ]
            ]
        ]
    ],

    //    TODO: Revert this after aggregator to reseller migration is complete (PLAT-33)
    'testRestoreApplication' => [
        'request'  => [
            'url'     => '/applications/restore',
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
                'app_ids_to_restore' => ['apptorestore01', 'apptorestore02'],
                'app_ids_to_delete'  => ['apptodelete000']
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],
];
