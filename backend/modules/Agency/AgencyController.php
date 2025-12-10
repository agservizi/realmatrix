<?php

require_once __DIR__ . '/AgencyModel.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/RateLimitMiddleware.php';

class AgencyController extends Controller
{
    private AgencyModel $model;

    public function __construct()
    {
        $this->model = new AgencyModel();
    }

    public function register(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        RateLimitMiddleware::handle('register_' . $ip);
        $data = $this->input();
        if (!isset($data['agency_name'], $data['name'], $data['email'], $data['password'])) {
            $this->bad('Missing fields');
        }
        $result = $this->model->createAgency($data['agency_name'], $data['name'], $data['email'], $data['password']);
        $this->ok($result);
    }

    public function collaborators(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('config_manage');
        $list = $this->model->listCollaborators((int)$user['agency_id']);
        foreach ($list as &$collab) {
            $collab['permissions'] = $this->model->getPermissionsByUserId((int)$collab['id']);
        }
        $this->ok($list);
    }

    public function createCollaborator(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('config_manage');
        $data = $this->input();
        if (!isset($data['name'], $data['email'], $data['password'], $data['role'])) {
            $this->bad('Missing collaborator fields');
        }
        $data['permissions'] = $data['permissions'] ?? [];
        $id = $this->model->createCollaborator((int)$user['agency_id'], $data);
        $this->ok(['id' => $id]);
    }

    public function updateCollaborator(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('config_manage');
        $targetId = (int)($params['id'] ?? 0);
        $data = $this->input();
        $ok = $this->model->updateCollaborator((int)$user['agency_id'], $targetId, $data);
        if (!$ok) {
            $this->bad('Unable to update collaborator', 500);
        }
        $this->ok(['updated' => true]);
    }

    public function deleteCollaborator(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('config_manage');
        $targetId = (int)($params['id'] ?? 0);
        $ok = $this->model->deactivateCollaborator((int)$user['agency_id'], $targetId);
        if (!$ok) {
            $this->bad('Unable to deactivate collaborator', 500);
        }
        $this->ok(['deleted' => true]);
    }
}
