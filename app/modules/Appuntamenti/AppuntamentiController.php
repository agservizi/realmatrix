<?php
namespace App\Modules\Appuntamenti;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;

class AppuntamentiController extends Controller
{
    private AppuntamentiModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new AppuntamentiModel($db);
    }

    public function create(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $missing = Validator::require($request['body'], ['titolo', 'data_appuntamento']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->create($agencyId, $request['body']);
        Response::json(['id' => $id], 201);
    }

    public function list(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        Response::json($this->model->list($agencyId));
    }

    public function update(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $id = (int)$request['params']['id'];
        $missing = Validator::require($request['body'], ['titolo', 'data_appuntamento']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $ok = $this->model->update($agencyId, $id, $request['body']);
        Response::json(['updated' => $ok]);
    }
}
