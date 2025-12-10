<?php
require_once __DIR__ . '/../../core/Env.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/RateLimiter.php';
require_once __DIR__ . '/../../core/JWT.php';

Env::load(__DIR__ . '/../../../.env');

function assertTrue($cond, $msg)
{
    if (!$cond) {
        echo "FAIL: {$msg}\n";
        exit(1);
    }
}

// Rate limiter window test
$limiter = new RateLimiter(__DIR__ . '/../../storage/ratelimit_tests');
$key = 'tst_' . uniqid();
for ($i = 0; $i < 3; $i++) {
    assertTrue($limiter->hit($key, 3, 60) === true, 'Rate limit should allow');
}
assertTrue($limiter->hit($key, 3, 60) === false, 'Rate limit should block after max');

// JWT access/refresh
$user = ['id' => 1, 'agency_id' => 1, 'role' => 'admin'];
$perms = ['immobili_manage'];
$tokens = Auth::issueTokens($user, $perms);
assertTrue(isset($tokens['access_token'], $tokens['refresh_token']), 'Tokens returned');
$decoded = JWT::decode($tokens['access_token']);
assertTrue($decoded['role'] === 'admin', 'Access token carries role');
$newTokens = Auth::refresh($tokens['refresh_token']);
assertTrue(isset($newTokens['access_token']), 'Refresh returns new access token');

echo "PASS: security tests\n";
