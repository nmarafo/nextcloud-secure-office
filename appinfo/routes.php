<?php
declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#status', 'url' => '/api/v1/status', 'verb' => 'GET'],
    ]
];
