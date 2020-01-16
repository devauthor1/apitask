<?php

/**
 * Sources config array
 */
$setup = [
    'file' => [
        'directories' => [
            '../data',
            '../old-data'
        ],
        'fileExtension' => 'csv',
        'separator' => ',',
    ],
    'db' => [
        'dbNames' => [
            'db1' => [
                'dbhost' => 'localhost',
                'dbname' => 'stats1',
                'dbuser' => 'root',
                'dbpassword' => 'dbdb123',
            ],
            'db2' => [
                'dbhost' => 'localhost',
                'dbname' => 'stats2',
                'dbuser' => 'root',
                'dbpassword' => 'dbdb123',
            ],
        ],
    ],
    'api' => [
        'apiNames' => [
            'google' => [
                'url' => 'https://example-first.com/api/',
            ],
            'amazon' => [
                'url' => 'https://example-second.com/api/',
            ],
        ],
    ],
];
