<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PermissionMiddleware.php';
require_once __DIR__ . '/ContrattiModel.php';
require_once __DIR__ . '/PdfService.php';

class ContrattiController extends Controller
{
    private ContrattiModel $model;

    public function __construct()
    {
        $this->model = new ContrattiModel();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('contratti_manage');
        $pg = $this->pagination();
        $data = $this->model->listByAgency((int)$user['agency_id'], $pg['limit'], $pg['offset']);
        $total = $this->model->countByAgency((int)$user['agency_id']);
        $this->ok(['items' => $data, 'total' => $total, 'page' => $pg['page'], 'limit' => $pg['limit']]);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('contratti_manage');
        $payload = $this->input();
        $required = ['nome', 'valore', 'body'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                $this->bad('Missing field ' . $field);
            }
        }

        // Create DB row first to obtain ID
        $id = $this->model->create((int)$user['agency_id'], [
            'nome' => $payload['nome'],
            'valore' => $payload['valore'],
            'pdf_path' => ''
        ]);

        $pdfPath = PdfService::generateSimplePdf(
            $payload['nome'],
            (string)$payload['body'],
            __DIR__ . '/../../storage/contratti',
            'contratto_' . $id . '.pdf'
        );
        $this->model->updatePdfPath($id, $pdfPath);

        $this->ok(['id' => $id, 'pdf_path' => $pdfPath]);
    }

    public function delete(array $params): void
    {
        $user = AuthMiddleware::handle();
        PermissionMiddleware::handle('contratti_manage');
        $id = (int)($params['id'] ?? 0);
        $ok = $this->model->delete((int)$user['agency_id'], $id);
        if (!$ok) {
            $this->bad('Not found or not owned', 404);
        }
        $this->ok(['deleted' => true]);
    }
}
