<?php
namespace App\Modules\Fatture;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;
use App\Core\Pdf;

class FattureController extends Controller
{
    private FattureModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new FattureModel($db);
    }

    public function create(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $missing = Validator::require($request['body'], ['numero', 'importo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->create($agencyId, $request['body']);
        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $pdfPath = $uploadDir . '/fattura_' . $id . '.pdf';
        Pdf::simpleText(
            'Fattura #' . $id,
            [
                'Numero: ' . ($request['body']['numero'] ?? ''),
                'Cliente ID: ' . ($request['body']['cliente_id'] ?? ''),
                'Importo: ' . ($request['body']['importo'] ?? ''),
                'Stato: ' . ($request['body']['stato'] ?? ''),
            ],
            $pdfPath
        );
        $relative = str_replace(__DIR__ . '/../../../public', '', $pdfPath);
        $this->model->updatePdfPath($agencyId, $id, $relative);
        Response::json(['id' => $id, 'pdf_path' => $relative], 201);
    }

    public function list(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        Response::json($this->model->list($agencyId));
    }
}
