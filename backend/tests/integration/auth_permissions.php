<?php
require_once __DIR__ . '/../../core/Env.php';
require_once __DIR__ . '/../../core/JWT.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';

Env::load(__DIR__ . '/../../../.env');

function assertTrue($cond, $msg)
{
    if (!$cond) {
        echo "FAIL: {$msg}\n";
        exit(1);
    }
}

$payload = [
    'user_id' => 1,
    'agency_id' => 10,
    'role' => 'admin',
    'permissions' => ['immobili_manage', 'config_manage']
];
$token = JWT::encode($payload, 60);
$decoded = JWT::decode($token);
assertTrue(is_array($decoded), 'JWT decode should return array');
assertTrue($decoded['agency_id'] === 10, 'agency_id preserved in token');
assertTrue(in_array('immobili_manage', $decoded['permissions'], true), 'permission preserved');

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
PermissionMiddleware::handle('immobili_manage');

echo "PASS: auth + permission middleware basic flow\n";
