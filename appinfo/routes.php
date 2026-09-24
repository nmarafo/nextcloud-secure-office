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
        // Rutas para reglas por archivo y combinación archivo-usuario
        ['name' => 'settings_api#getFileRules', 'url' => '/api/v1/file-rules', 'verb' => 'GET'],
        ['name' => 'settings_api#saveFileRule', 'url' => '/api/v1/file-rules', 'verb' => 'POST'],
        ['name' => 'settings_api#deleteFileRule', 'url' => '/api/v1/file-rules/{ruleId}', 'verb' => 'DELETE'],
        ['name' => 'settings_api#searchFiles', 'url' => '/api/v1/files/search', 'verb' => 'GET'],
    ]
];
