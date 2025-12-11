<?php
session_start();

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

$logFile = __DIR__ . '/../storage/logs/app.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0775, true);
}

$handleException = function (\Throwable $e) use ($logFile) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    $debug = ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG')) === 'true' || ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG')) === '1';
    $msg = $debug ? $e->getMessage() : 'Internal Server Error';
    http_response_code(500);
    echo $msg;
    exit;
};

set_exception_handler($handleException);

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

use App\Core\Router;
use App\Core\Database;
use App\Core\JWT;
use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Permissions;
use App\Core\Response;
use App\Modules\Auth\AuthController;
use App\Modules\Agency\AgencyController;
use App\Modules\Users\UsersController;
use App\Modules\Immobili\ImmobiliController;
use App\Modules\Clienti\ClientiController;
use App\Modules\Lead\LeadController;
use App\Modules\Appuntamenti\AppuntamentiController;
use App\Modules\Contratti\ContrattiController;
use App\Modules\Documenti\DocumentiController;
use App\Modules\Fatture\FattureController;
use App\Modules\HomeSharing\HomeSharingController;

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
try {
    $db = new Database($config);
} catch (\Throwable $e) {
    throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
}

$jwt = new JWT($config);
$authService = new Auth($db, $jwt);

$router = new Router();
$router->addMiddleware('auth', Middleware::auth($authService));
$router->addMiddleware('agency', Middleware::agencyScope());
$router->addMiddleware('csrf', Middleware::csrf($config['csrf']['secret']));
$router->addMiddleware('rate', Middleware::rateLimit(100, 60));
$router->addMiddleware('perm_immobili', Middleware::permission('immobili'));
$router->addMiddleware('perm_clienti', Middleware::permission('clienti'));
$router->addMiddleware('perm_lead', Middleware::permission('lead'));
$router->addMiddleware('perm_appuntamenti', Middleware::permission('appuntamenti'));
$router->addMiddleware('perm_contratti', Middleware::permission('contratti'));
$router->addMiddleware('perm_documenti', Middleware::permission('documenti'));
$router->addMiddleware('perm_fatture', Middleware::permission('fatture'));
$router->addMiddleware('perm_sharing', Middleware::permission('home-sharing'));
$router->addMiddleware('perm_impostazioni', Middleware::permission('impostazioni'));

$authCtrl = new AuthController($config, $db, $jwt);
$agencyCtrl = new AgencyController($config, $db, $jwt);
$usersCtrl = new UsersController($config, $db);
$immobiliCtrl = new ImmobiliController($config, $db);
$clientiCtrl = new ClientiController($config, $db);
$leadCtrl = new LeadController($config, $db);
$appCtrl = new AppuntamentiController($config, $db);
$contrattiCtrl = new ContrattiController($config, $db);
$docCtrl = new DocumentiController($config, $db);
$fattureCtrl = new FattureController($config, $db);
$sharingCtrl = new HomeSharingController($config, $db);

// UI routes
$router->add('GET', '/', function () use ($authService) {
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    $bearer = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';
    $token = $_COOKIE['rm_token'] ?? $bearer;
    $user = $token ? $authService->validateToken($token) : null;
    if ($user) {
        header('Location: /dashboard');
        return;
    }
    header('Location: /login');
});
$router->add('GET', '/login', function () {
    include __DIR__ . '/../views/login.php';
});
$router->add('GET', '/dashboard', function () use ($config) {
    $templatePath = __DIR__ . '/../views/dashboard.php';
    include __DIR__ . '/../views/layout.php';
}, ['auth']);
$router->add('GET', '/immobili', function () use ($config) {
    $templatePath = __DIR__ . '/../views/immobili.php';
    include __DIR__ . '/../views/layout.php';
}, ['auth']);
$router->add('GET', '/clienti', function () use ($config) {
    $templatePath = __DIR__ . '/../views/clienti.php';
    include __DIR__ . '/../views/layout.php';
}, ['auth']);
$router->add('GET', '/collaboratori', function () use ($config) {
    $templatePath = __DIR__ . '/../views/collaboratori.php';
    include __DIR__ . '/../views/layout.php';
}, ['auth']);
$router->add('GET', '/sharing', function () use ($config) {
    $templatePath = __DIR__ . '/../views/sharing.php';
    include __DIR__ . '/../views/layout.php';
}, ['auth']);
$router->add('POST', '/logout', function () {
    setcookie('rm_token', '', ['expires' => time() - 3600, 'path' => '/']);
    header('Location: /login');
}, ['auth']);

// API routes
$router->add('POST', '/api/v1/auth/login', [$authCtrl, 'login'], ['rate']);
$router->add('GET', '/api/v1/auth/me', [$authCtrl, 'me'], ['auth']);
$router->add('POST', '/api/v1/auth/logout', [$authCtrl, 'logout'], ['auth']);

$router->add('POST', '/api/v1/agency/register', [$agencyCtrl, 'register'], ['rate']);

$router->add('POST', '/api/v1/collaborators', [$usersCtrl, 'create'], ['auth', 'csrf', 'perm_impostazioni']);
$router->add('GET', '/api/v1/collaborators', [$usersCtrl, 'list'], ['auth', 'perm_impostazioni']);
$router->add('PUT', '/api/v1/collaborators/{id}', [$usersCtrl, 'update'], ['auth', 'csrf', 'perm_impostazioni']);
$router->add('DELETE', '/api/v1/collaborators/{id}', [$usersCtrl, 'delete'], ['auth', 'csrf', 'perm_impostazioni']);
$router->add('GET', '/api/v1/permissions', [$usersCtrl, 'permissions'], ['auth']);

$router->add('POST', '/api/v1/immobili', [$immobiliCtrl, 'create'], ['auth', 'csrf', 'perm_immobili']);
$router->add('GET', '/api/v1/immobili', [$immobiliCtrl, 'list'], ['auth', 'perm_immobili']);
$router->add('PUT', '/api/v1/immobili/{id}', [$immobiliCtrl, 'update'], ['auth', 'csrf', 'perm_immobili']);
$router->add('DELETE', '/api/v1/immobili/{id}', [$immobiliCtrl, 'delete'], ['auth', 'csrf', 'perm_immobili']);

$router->add('POST', '/api/v1/clienti', [$clientiCtrl, 'create'], ['auth', 'csrf', 'perm_clienti']);
$router->add('GET', '/api/v1/clienti', [$clientiCtrl, 'list'], ['auth', 'perm_clienti']);
$router->add('PUT', '/api/v1/clienti/{id}', [$clientiCtrl, 'update'], ['auth', 'csrf', 'perm_clienti']);

$router->add('POST', '/api/v1/lead', [$leadCtrl, 'create'], ['auth', 'csrf', 'perm_lead']);
$router->add('GET', '/api/v1/lead', [$leadCtrl, 'list'], ['auth', 'perm_lead']);
$router->add('PUT', '/api/v1/lead/{id}', [$leadCtrl, 'update'], ['auth', 'csrf', 'perm_lead']);

$router->add('POST', '/api/v1/appuntamenti', [$appCtrl, 'create'], ['auth', 'csrf', 'perm_appuntamenti']);
$router->add('GET', '/api/v1/appuntamenti', [$appCtrl, 'list'], ['auth', 'perm_appuntamenti']);
$router->add('PUT', '/api/v1/appuntamenti/{id}', [$appCtrl, 'update'], ['auth', 'csrf', 'perm_appuntamenti']);

$router->add('POST', '/api/v1/contratti', [$contrattiCtrl, 'create'], ['auth', 'csrf', 'perm_contratti']);
$router->add('GET', '/api/v1/contratti', [$contrattiCtrl, 'list'], ['auth', 'perm_contratti']);

$router->add('POST', '/api/v1/documenti', [$docCtrl, 'upload'], ['auth', 'csrf', 'perm_documenti']);
$router->add('GET', '/api/v1/documenti', [$docCtrl, 'list'], ['auth', 'perm_documenti']);

$router->add('POST', '/api/v1/fatture', [$fattureCtrl, 'create'], ['auth', 'csrf', 'perm_fatture']);
$router->add('GET', '/api/v1/fatture', [$fattureCtrl, 'list'], ['auth', 'perm_fatture']);

$router->add('GET', '/api/v1/sharing/immobili', [$sharingCtrl, 'listImmobili'], ['auth', 'perm_sharing']);
$router->add('POST', '/api/v1/sharing/immobili', [$sharingCtrl, 'share'], ['auth', 'csrf', 'perm_sharing']);
$router->add('GET', '/api/v1/sharing/agenzie', [$sharingCtrl, 'listAgenzie'], ['auth', 'perm_sharing']);
$router->add('POST', '/api/v1/sharing/request', [$sharingCtrl, 'createRequest'], ['auth', 'csrf', 'perm_sharing']);
$router->add('POST', '/api/v1/sharing/messages', [$sharingCtrl, 'sendMessage'], ['auth', 'csrf', 'perm_sharing']);
$router->add('GET', '/api/v1/sharing/messages', [$sharingCtrl, 'listMessages'], ['auth', 'perm_sharing']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
