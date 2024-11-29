<?php

return [
    'database' => [
        'host' => 'api.qlsportsonline.com',
        'port' => 3306,
        'dbname' => 'eehpknmj_qlsports',
        'charset' => 'utf8mb4',
    ],

    'services' => [
        'apitoken' => $_ENV['APPTOKEN'] ?? null
    ]
];