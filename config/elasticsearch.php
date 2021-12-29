<?php

return [
    'servers' => explode(',', env('ELASTICSEARCH_SERVERS', 'localhost:9200')),
    'retries' => env('ELASTICSEARCH_RETRIES', 2),
    'password' => env('ELASTICSEARCH_PASSWORD', '1234'),
    'prefix' => env('ELASTICSEARCH_PREFIX'),
    'settings' => [
        'mapping' => [
            'total_fields' => [ 'limit' => 5000 ]
        ],
        'number_of_shards' => 1,
        'number_of_replicas' => 1,
        //'refresh_interval' => '10s',
        //'max_result_window' => 100000,
        //'index' => [ 'blocks' => [ 'read_only_allow_delete' => null ] ],
        'analysis' => [
            'filter' => [
                'email' => [
                    'type' => 'pattern_capture',
                    'preserve_original' => true,
                    'patterns' => [
                        '([^@]+)',
                        '(\\p{L}+)',
                        '(\\d+)',
                        '@(.+)',
                        '([^-@]+)',
                    ]
                ]
            ],
            'analyzer' => [
                'default' => [
                    'tokenizer' => 'standard',
                    'filter' => [
                        'lowercase',
                        'apostrophe',
                        'asciifolding'
                    ]
                ],
                'email' => [
                    'tokenizer' => 'uax_url_email',
                    'filter' => [
                        'email',
                        'lowercase'
                    ]
                ]
            ],
            'normalizer' => [
                'keyword_normalizer' => [
                    'type' => 'custom',
                    'char_filter' => [],
                    'filter' => [
                        'lowercase',
                        'asciifolding'
                    ]
                ]
            ]
        ]
    ]
];
