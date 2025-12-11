<?php

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../modules/Agency/AgencyController.php';
require_once __DIR__ . '/../modules/Users/UserController.php';
require_once __DIR__ . '/../modules/Permissions/PermissionController.php';
require_once __DIR__ . '/../modules/Immobili/ImmobiliController.php';
require_once __DIR__ . '/../modules/Clienti/ClientiController.php';
require_once __DIR__ . '/../modules/Lead/LeadController.php';
require_once __DIR__ . '/../modules/Appuntamenti/AppuntamentiController.php';
require_once __DIR__ . '/../modules/Contratti/ContrattiController.php';
require_once __DIR__ . '/../modules/Fatture/FattureController.php';
require_once __DIR__ . '/../modules/Documenti/DocumentiController.php';
require_once __DIR__ . '/../modules/HomeSharing/HomeSharingController.php';

Env::load();

// Force PHP error log into backend/storage/app.log for shared hosting visibility
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/app.log');
error_reporting(E_ALL);

$router = new Router();

$agency = new AgencyController();
$users = new UserController();
$permissions = new PermissionController();
$immobili = new ImmobiliController();
$clienti = new ClientiController();
$lead = new LeadController();
$app = new AppuntamentiController();
$contratti = new ContrattiController();
$fatture = new FattureController();
$documenti = new DocumentiController();
$hs = new HomeSharingController();

// Authentication
$router->add('POST', '/auth/login', fn() => $users->login());
$router->add('POST', '/auth/refresh', fn() => $users->refresh());

// Agency registration and collaborators
$router->add('POST', '/agency/register', fn() => $agency->register());
$router->add('GET', '/agency/collaborators', fn() => $agency->collaborators([]));
$router->add('POST', '/agency/collaborators', fn() => $agency->createCollaborator());
$router->add('PUT', '/agency/collaborators/{id}', fn($params) => $agency->updateCollaborator($params));
$router->add('DELETE', '/agency/collaborators/{id}', fn($params) => $agency->deleteCollaborator($params));

// Permissions
$router->add('GET', '/agency/permissions', fn() => $permissions->list());

// Domain placeholders
$router->add('GET', '/immobili', fn() => $immobili->list());
$router->add('POST', '/immobili', fn() => $immobili->create());
$router->add('PUT', '/immobili/{id}', fn($params) => $immobili->update($params));
$router->add('DELETE', '/immobili/{id}', fn($params) => $immobili->delete($params));

$router->add('GET', '/clienti', fn() => $clienti->list());
$router->add('POST', '/clienti', fn() => $clienti->create());
$router->add('PUT', '/clienti/{id}', fn($params) => $clienti->update($params));
$router->add('DELETE', '/clienti/{id}', fn($params) => $clienti->delete($params));

$router->add('GET', '/lead', fn() => $lead->list());
$router->add('POST', '/lead', fn() => $lead->create());
$router->add('PUT', '/lead/{id}', fn($params) => $lead->update($params));
$router->add('DELETE', '/lead/{id}', fn($params) => $lead->delete($params));

$router->add('GET', '/appuntamenti', fn() => $app->list());
$router->add('POST', '/appuntamenti', fn() => $app->create());
$router->add('PUT', '/appuntamenti/{id}', fn($params) => $app->update($params));
$router->add('DELETE', '/appuntamenti/{id}', fn($params) => $app->delete($params));

$router->add('GET', '/contratti', fn() => $contratti->list());
$router->add('POST', '/contratti', fn() => $contratti->create());
$router->add('DELETE', '/contratti/{id}', fn($params) => $contratti->delete($params));

$router->add('GET', '/fatture', fn() => $fatture->list());
$router->add('POST', '/fatture', fn() => $fatture->create());
$router->add('PUT', '/fatture/{id}', fn($params) => $fatture->update($params));
$router->add('DELETE', '/fatture/{id}', fn($params) => $fatture->delete($params));

$router->add('GET', '/documenti', fn() => $documenti->list());
$router->add('POST', '/documenti', fn() => $documenti->upload());
$router->add('DELETE', '/documenti/{id}', fn($params) => $documenti->delete($params));

$router->add('GET', '/homesharing', fn() => $hs->list());
$router->add('POST', '/homesharing', fn() => $hs->create());
$router->add('PUT', '/homesharing/{id}', fn($params) => $hs->updateStatus($params));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
