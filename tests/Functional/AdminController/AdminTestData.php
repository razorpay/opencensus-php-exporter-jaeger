<?php

return [
    'fetchMultiple' => [
        'request'  => [
            'url'     => 'admin/entities/%s',
            'method'  => 'GET',
            'content' => [
                'count' => 20,
                'skip'  => 0
            ]
        ],
        'response' => [
            'entity' => 'collection',
            'count'  => 1,
            'admin'  => true,
            'items'  => []
        ],
    ],

    'fetchById' => [
        'request'  => [
            'url'     => 'admin/entities/%s/%s',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [],
    ]
];
