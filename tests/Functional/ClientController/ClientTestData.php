<?php

return [

    'testCreateClients' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'name'        => 'apptest',
                'website'     => 'https://www.example.com',
                'type'        => 'partner'
            ]
        ]
    ],

    'testCreateClientsWithNoMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Validation failed. The merchant id field is required.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => 'Razorpay\Spine\Exception\ValidationFailureException',
            'message' => 'Validation failed. The merchant id field is required.',
        ]
    ],

    'testCreateClientsWithWrongMerchantId' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'merchant_id' => '1000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Validation failed. The merchant id must be 14 characters.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => 'Razorpay\Spine\Exception\ValidationFailureException',
            'message' => 'Validation failed. The merchant id must be 14 characters.',
        ]
    ],

    'testDeleteClients' => [
        'request'  => [
            'method'  => 'DELETE',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteNoRecordClients' => [
        'request'  => [
            'method'  => 'DELETE',
            'content' => [
                'merchant_id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'No records found with the given Id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'   => 'Razorpay\OAuth\Exception\DBQueryException',
            'message' => 'No records found with the given Id',
        ]
    ],

    'testDeleteClientsWithNoMerchant' => [
        'request'  => [
            'method'  => 'DELETE',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Validation failed. The merchant id field is required.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => 'Razorpay\Spine\Exception\ValidationFailureException',
            'message' => 'Validation failed. The merchant id field is required.',
        ]
    ],

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
