<?php
namespace App\Modules\Documenti;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;

class DocumentiController extends Controller
{
    private DocumentiModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new DocumentiModel($db);
    }

    public function upload(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $file = $request['files']['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::json(['error' => 'File missing'], 400);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['application/pdf', 'image/png', 'image/jpeg'];
        if (!in_array($mime, $allowed, true)) {
            Response::json(['error' => 'Invalid file type'], 415);
            return;
        }

        $missing = Validator::require($request['body'], ['titolo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $dest = $uploadDir . '/' . uniqid('doc_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $dest);
        $relative = str_replace(__DIR__ . '/../../../public', '', $dest);

        $id = $this->model->store($agencyId, [
            'titolo' => $request['body']['titolo'] ?? $file['name'],
            'tag' => $request['body']['tag'] ?? '',
            'path' => $relative,
            'mime' => $mime,
        ]);
        Response::json(['id' => $id, 'path' => $relative], 201);
    }

    public function list(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        Response::json($this->model->list($agencyId));
    }
}
