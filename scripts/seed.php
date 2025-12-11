<?php
// Basic seed: creates demo agency + admin if not exists

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
use App\Core\Permissions;

$db = new Database($config);
$pdo = $db->pdo();

$agencyCheck = $pdo->query("SELECT id FROM agencies WHERE email = 'demo@realmatrix.local' LIMIT 1")->fetch();
if ($agencyCheck) {
    echo "Seed already applied.\n";
    exit(0);
}

$pdo->beginTransaction();
$pdo->prepare('INSERT INTO agencies (name, email, phone, created_at) VALUES (?,?,?,NOW())')
    ->execute(['Demo Agency', 'demo@realmatrix.local', '000',]);
$agencyId = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO users (agency_id, name, email, password, role, permissions, active, created_at) VALUES (?,?,?,?,?,?,1,NOW())')
    ->execute([
        $agencyId,
        'Admin Demo',
        'admin@realmatrix.local',
        password_hash('password', PASSWORD_BCRYPT),
        'admin',
        json_encode(Permissions::all()),
    ]);

$pdo->commit();

echo "Seed inserted (demo agency + admin).\n";
