<?php
// Basic configuration loader

return [
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'app_key' => getenv('APP_KEY') ?: 'changeme-secret-key',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'realmatrix',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'super-secret-jwt',
        'issuer' => getenv('JWT_ISS') ?: 'realmatrix',
        'expiration' => (int)(getenv('JWT_EXP') ?: 3600),
    ],
    'csrf' => [
        'secret' => getenv('CSRF_SECRET') ?: 'csrf-secret-key',
    ],
];
