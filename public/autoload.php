<?php
// Fallback autoloader: tries ../app then ./app; if not found, requires core Autoload (handles more paths)
if (!file_exists(__DIR__ . '/../app/Core/Database.php') && file_exists(__DIR__ . '/../app/Core/Autoload.php')) {
    require __DIR__ . '/../app/Core/Autoload.php';
    return;
}
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $baseDirs = [__DIR__ . '/../app/', __DIR__ . '/app/'];
    foreach ($baseDirs as $base_dir) {
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
