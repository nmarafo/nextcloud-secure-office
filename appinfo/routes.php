<?php
declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#status', 'url' => '/api/v1/status', 'verb' => 'GET'],
        ['name' => 'settings_api#updateSettings', 'url' => '/api/v1/settings', 'verb' => 'POST'],
        ['name' => 'settings_api#getDiagnostics', 'url' => '/api/v1/diagnostics', 'verb' => 'GET'],
        ['name' => 'settings_api#exportAudit', 'url' => '/api/v1/audit/export', 'verb' => 'GET'],
        ['name' => 'settings_api#getGroups', 'url' => '/api/v1/groups', 'verb' => 'GET'],
    ]
];
