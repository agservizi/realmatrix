<?php
// includes/config.php
// Load .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $ini = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if (is_array($ini)) {
        foreach ($ini as $k => $v) {
            if (!getenv($k)) { putenv("$k=$v"); }
        }
    }
}

$dbHost = getenv('DOMUS_DB_HOST') ?: 'localhost';
$dbName = getenv('DOMUS_DB_NAME') ?: 'domuscore';
$dbUser = getenv('DOMUS_DB_USER') ?: 'domus_user';
$dbPass = getenv('DOMUS_DB_PASS') ?: 'change_me';

$storageCdnBase = getenv('DOMUS_STORAGE_CDN') ?: '';
$storageBucket = getenv('DOMUS_STORAGE_BUCKET') ?: '';
$webhookSecret = getenv('DOMUS_WEBHOOK_SECRET') ?: 'replace_with_strong_secret';
if (!defined('WEBHOOK_SECRET')) {
    define('WEBHOOK_SECRET', $webhookSecret);
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        $options
    );
} catch (PDOException $e) {
    error_log('DB connection failed: '.$e->getMessage());
    http_response_code(500);
    exit('Database connection error');
}
