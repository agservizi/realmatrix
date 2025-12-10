<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/AppuntamentiModel.php';

class AppuntamentiController extends Controller
{
    private AppuntamentiModel $model;

    public function __construct()
    {
        $this->model = new AppuntamentiModel();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('appuntamenti_manage');
        $pg = $this->pagination();
        $data = $this->model->listByAgency((int)$user['agency_id'], $pg['limit'], $pg['offset']);
        $total = $this->model->countByAgency((int)$user['agency_id']);
        $this->ok(['items' => $data, 'total' => $total, 'page' => $pg['page'], 'limit' => $pg['limit']]);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('appuntamenti_manage');
        $payload = $this->input();
        $required = ['titolo', 'data_app'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                $this->bad('Missing field ' . $field);
            }
        }
        $payload['note'] = $payload['note'] ?? '';
        $id = $this->model->create((int)$user['agency_id'], $payload);
        $this->ok(['id' => $id]);
    }

    public function update(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('appuntamenti_manage');
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
        PermissionMiddleware::handle('appuntamenti_manage');
        $id = (int)($params['id'] ?? 0);
        $ok = $this->model->delete((int)$user['agency_id'], $id);
        if (!$ok) {
            $this->bad('Not found or not owned', 404);
        }
        $this->ok(['deleted' => true]);
    }
}
