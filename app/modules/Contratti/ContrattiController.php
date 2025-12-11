<?php
namespace App\Modules\Contratti;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;
use App\Core\Pdf;

class ContrattiController extends Controller
{
    private ContrattiModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new ContrattiModel($db);
    }

    public function create(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $missing = Validator::require($request['body'], ['titolo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->create($agencyId, $request['body']);

        // Generate simple PDF stub
        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $pdfPath = $uploadDir . '/contratto_' . $id . '.pdf';
        Pdf::simpleText(
            'Contratto #' . $id,
            [
                'Titolo: ' . ($request['body']['titolo'] ?? ''),
                'Cliente ID: ' . ($request['body']['cliente_id'] ?? ''),
                'Immobile ID: ' . ($request['body']['immobile_id'] ?? ''),
                'Valore: ' . ($request['body']['valore'] ?? ''),
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
