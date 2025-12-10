<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/DocumentiModel.php';
require_once __DIR__ . '/UploadService.php';

class DocumentiController extends Controller
{
    private DocumentiModel $model;

    public function __construct()
    {
        $this->model = new DocumentiModel();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('documenti_manage');
        $pg = $this->pagination();
        $data = $this->model->listByAgency((int)$user['agency_id'], $pg['limit'], $pg['offset']);
        $total = $this->model->countByAgency((int)$user['agency_id']);
        $this->ok(['items' => $data, 'total' => $total, 'page' => $pg['page'], 'limit' => $pg['limit']]);
    }

    public function upload(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('documenti_manage');
        if (!empty($_FILES['file'])) {
            $titolo = $_POST['titolo'] ?? ($_FILES['file']['name'] ?? 'Documento');
            $path = UploadService::saveUploaded($_FILES['file'], __DIR__ . '/../../storage/documenti');
        } else {
            $payload = $this->input();
            $required = ['titolo', 'filename', 'file_base64'];
            foreach ($required as $field) {
                if (!isset($payload[$field])) {
                    $this->bad('Missing field ' . $field);
                }
            }
            $titolo = $payload['titolo'];
            $path = UploadService::saveBase64(
                $payload['file_base64'],
                __DIR__ . '/../../storage/documenti',
                basename($payload['filename'])
            );
        }
        $id = $this->model->create((int)$user['agency_id'], [
            'titolo' => $titolo,
            'file_path' => $path
        ]);
        $this->ok(['id' => $id, 'file_path' => $path]);
    }

    public function delete(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('documenti_manage');
        $id = (int)($params['id'] ?? 0);
        $ok = $this->model->delete((int)$user['agency_id'], $id);
        if (!$ok) {
            $this->bad('Not found or not owned', 404);
        }
        $this->ok(['deleted' => true]);
    }
}
