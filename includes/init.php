<?php
// includes/init.php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

function generate_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && $token && hash_equals($_SESSION['csrf_token'], $token);
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role(): ?string {
    return $_SESSION['role'] ?? null;
}

function require_role(array $roles): void {
    if (!in_array(current_user_role(), $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function log_activity(string $action, array $meta = []): void {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, agency_id, action, meta) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            current_user_id(),
            $_SESSION['agency_id'] ?? null,
            $action,
            json_encode($meta)
        ]);
    } catch (Throwable $e) {
        error_log('Activity log failed: '.$e->getMessage());
    }
}

// Simple in-memory (session) rate limiter per key
function enforce_rate_limit(string $key, int $limit, int $windowSeconds): void {
    $now = time();
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    // discard old hits
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        fn($ts) => ($now - $ts) < $windowSeconds
    );
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        http_response_code(429);
        exit('Too many requests');
    }
    $_SESSION['rate_limit'][$key][] = $now;
}

function sanitize_filename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name);
    return trim($name, '_') ?: 'file';
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/queue.php';
