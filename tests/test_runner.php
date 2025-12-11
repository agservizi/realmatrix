<?php
// Minimal test runner: php tests/test_runner.php

require __DIR__ . '/../app/core/JWT.php';
require __DIR__ . '/../app/core/Permissions.php';
require __DIR__ . '/../app/core/Router.php';
require __DIR__ . '/../app/core/Response.php';

use App\Core\JWT;
use App\Core\Permissions;
use App\Core\Router;
use App\Core\Response;

$tests = [];

$tests['jwt_encode_decode'] = function () {
    $jwt = new JWT(['jwt' => ['secret' => 's', 'issuer' => 'i', 'expiration' => 60]]);
    $token = $jwt->encode(['sub' => 1]);
    $decoded = $jwt->decode($token);
    if ($decoded['sub'] !== 1) {
        throw new Exception('JWT sub mismatch');
    }
};

$tests['permissions_check'] = function () {
    $perms = ['immobili', 'lead'];
    if (!Permissions::hasPermission($perms, 'lead')) {
        throw new Exception('Should have lead');
    }
    if (Permissions::hasPermission($perms, 'fatture')) {
        throw new Exception('Should not have fatture');
    }
};

$tests['router_match'] = function () {
    $router = new Router();
    $hit = false;
    $router->add('GET', '/ping/{id}', function ($req) use (&$hit) {
        if (($req['params']['id'] ?? '') === '123') {
            $hit = true;
        }
    });
    ob_start();
    $router->dispatch('GET', '/ping/123');
    ob_end_clean();
    if (!$hit) {
        throw new Exception('Route did not match');
    }
};

$total = count($tests);
$passed = 0;
foreach ($tests as $name => $fn) {
    try {
        $fn();
        $passed++;
        echo "[OK] $name\n";
    } catch (Exception $e) {
        echo "[FAIL] $name: " . $e->getMessage() . "\n";
    }
}

echo "Passed $passed/$total tests\n";
if ($passed !== $total) {
    exit(1);
}
