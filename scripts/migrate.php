<?php
// Simple migration runner: php scripts/migrate.php [--drop]

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$dotenv = __DIR__ . '/../config/.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

$config = require __DIR__ . '/../config/config.php';

use App\Core\Database;

$db = new Database($config);
$pdo = $db->pdo();

$drop = in_array('--drop', $argv, true);
if ($drop) {
    echo "!!! DROPPING existing tables (data loss) !!!\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = [
        'sharing_messages', 'sharing_requests', 'sharing_immobili',
        'fatture', 'documenti', 'contratti', 'appuntamenti', 'lead',
        'clienti', 'immobili', 'users', 'agencies'
    ];
    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}
$schemaFile = __DIR__ . '/../database/schema.sql';
if (!file_exists($schemaFile)) {
    echo "Schema file not found\n";
    exit(1);
}
$sql = file_get_contents($schemaFile);
$pdo->exec($sql);

echo "Migrations executed.\n";
