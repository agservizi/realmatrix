<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/ImmobiliModel.php';

class ImmobiliController extends Controller
{
    private ImmobiliModel $model;

    public function __construct()
    {
        $this->model = new ImmobiliModel();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('immobili_manage');
        $pg = $this->pagination();
        $data = $this->model->listByAgency((int)$user['agency_id'], $pg['limit'], $pg['offset']);
        $total = $this->model->countByAgency((int)$user['agency_id']);
        $this->ok(['items' => $data, 'total' => $total, 'page' => $pg['page'], 'limit' => $pg['limit']]);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('immobili_manage');
        $payload = $this->input();
        $required = ['titolo', 'descrizione', 'prezzo', 'stato'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                $this->bad('Missing field ' . $field);
            }
        }
        $id = $this->model->create((int)$user['agency_id'], $payload);
        $this->ok(['id' => $id]);
    }

    public function update(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('immobili_manage');
        $id = (int)($params['id'] ?? 0);
        $payload = $this->input();
        $ok = $this->model->update((int)$user['agency_id'], $id, $payload);
        if (!$ok) {
            $this->bad('Not found or not owned', 404);
        }
        $this->ok(['updated' => true]);
    }

    public function delete(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('immobili_manage');
        $id = (int)($params['id'] ?? 0);
        $ok = $this->model->delete((int)$user['agency_id'], $id);
        if (!$ok) {
            $this->bad('Not found or not owned', 404);
        }
        $this->ok(['deleted' => true]);
    }
}
