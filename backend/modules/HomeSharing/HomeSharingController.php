<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/HomeSharingModel.php';

class HomeSharingController extends Controller
{
    private HomeSharingModel $model;

    public function __construct()
    {
        $this->model = new HomeSharingModel();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('homesharing_manage');
        $pg = $this->pagination();
        $data = $this->model->listByAgency((int)$user['agency_id'], $pg['limit'], $pg['offset']);
        $total = $this->model->countByAgency((int)$user['agency_id']);
        $this->ok(['items' => $data, 'total' => $total, 'page' => $pg['page'], 'limit' => $pg['limit']]);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('homesharing_manage');
        $payload = $this->input();
        $required = ['to_agency_id', 'immobile_id', 'messaggio'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                $this->bad('Missing field ' . $field);
            }
        }
        $payload['stato'] = 'pending';
        $id = $this->model->create((int)$user['agency_id'], $payload);
        $this->ok(['id' => $id]);
    }

    public function updateStatus(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('homesharing_manage');
        $id = (int)($params['id'] ?? 0);
        $payload = $this->input();
        if (!isset($payload['stato'])) {
            $this->bad('Missing field stato');
        }
        $ok = $this->model->updateStatus((int)$user['agency_id'], $id, $payload['stato']);
        if (!$ok) {
            $this->bad('Not found or forbidden', 403);
        }
        $this->ok(['updated' => true]);
    }
}
