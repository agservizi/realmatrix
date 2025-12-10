<?php
require_once __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../../.env');

$host = Env::get('DB_HOST', 'localhost');
$user = Env::get('DB_USER', 'root');
$pass = Env::get('DB_PASS', '');
$db   = Env::get('DB_NAME', 'realmatrix');

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Connection error: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$migrationsDir = realpath(__DIR__ . '/../migrations');
if (!$migrationsDir) {
    fwrite(STDERR, "Migrations directory not found\n");
    exit(1);
}

$files = glob($migrationsDir . '/*.sql');
sort($files);
if (empty($files)) {
    fwrite(STDOUT, "No migrations found\n");
    exit(0);
}

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Cannot read $file\n");
        exit(1);
    }
    fwrite(STDOUT, "Running " . basename($file) . "...\n");
    if (!$mysqli->multi_query($sql)) {
        fwrite(STDERR, "Error in " . basename($file) . ": " . $mysqli->error . "\n");
        exit(1);
    }
    while ($mysqli->more_results() && $mysqli->next_result()) {
        // Drain results for multi_query
    }
}

fwrite(STDOUT, "Migrations applied successfully.\n");
