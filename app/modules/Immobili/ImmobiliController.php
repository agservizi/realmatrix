<?php
namespace App\Modules\Immobili;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;

class ImmobiliController extends Controller
{
    private ImmobiliModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new ImmobiliModel($db);
    }

    public function create(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $body = $request['body'];
        $missing = Validator::require($body, ['titolo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $uploads = $this->handleUploads($request['files']);
        $data = array_merge($body, $uploads);
        $id = $this->model->create($agencyId, $data);
        Response::json(['id' => $id], 201);
    }

    public function list(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $filters = $request['query'] ?? [];
        $items = $this->model->list($agencyId, $filters);
        Response::json($items);
    }

    public function update(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $id = (int)$request['params']['id'];
        $body = $request['body'];
        $missing = Validator::require($body, ['titolo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $uploads = $this->handleUploads($request['files']);
        $data = array_merge($body, $uploads);
        $ok = $this->model->update($agencyId, $id, $data);
        Response::json(['updated' => $ok]);
    }

    public function delete(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $id = (int)$request['params']['id'];
        $ok = $this->model->delete($agencyId, $id);
        Response::json(['deleted' => $ok]);
    }

    private function handleUploads(array $files): array
    {
        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $paths = [];
        foreach (['immagine', 'planimetria'] as $field) {
            if (!isset($files[$field]) || $files[$field]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmpName = $files[$field]['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (!in_array($mime, $allowed, true)) {
                continue;
            }
            $ext = pathinfo($files[$field]['name'], PATHINFO_EXTENSION);
            $dest = $uploadDir . '/' . uniqid($field . '_') . '.' . $ext;
            move_uploaded_file($tmpName, $dest);
            $paths[$field] = str_replace(__DIR__ . '/../../../public', '', $dest);
        }
        return $paths;
    }
}
