<?php
// Temporary diagnostic script - remove after use.
header('Content-Type: text/plain');

$paths = [
    'public/index.php' => __DIR__ . '/index.php',
    '../app/Core/Database.php' => __DIR__ . '/../app/Core/Database.php',
    './app/Core/Database.php' => __DIR__ . '/app/Core/Database.php',
    '../app/Core/Autoload.php' => __DIR__ . '/../app/Core/Autoload.php',
    './app/Core/Autoload.php' => __DIR__ . '/app/Core/Autoload.php',
    '../config/config.php' => __DIR__ . '/../config/config.php',
    './config/config.php' => __DIR__ . '/config/config.php',
    '../config/.env' => __DIR__ . '/../config/.env',
    './config/.env' => __DIR__ . '/config/.env',
];

foreach ($paths as $label => $path) {
    $exists = file_exists($path) ? 'YES' : 'NO';
    echo str_pad($label, 25, ' ') . " => " . $exists . "\n";
}

?>
