<?php
namespace App\Modules\HomeSharing;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;

class HomeSharingController extends Controller
{
    private HomeSharingModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new HomeSharingModel($db);
    }

    public function listImmobili(): void
    {
        Response::json($this->model->listImmobili());
    }

    public function share(array $request): void
    {
        $data = $request['body'];
        $data['agency_id'] = $request['user']['agency_id'];
        $missing = Validator::require($data, ['immobile_id']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->shareImmobile($data);
        Response::json(['id' => $id], 201);
    }

    public function listAgenzie(): void
    {
        Response::json($this->model->listAgenzie());
    }

    public function createRequest(array $request): void
    {
        $data = $request['body'];
        $data['from_agency'] = $request['user']['agency_id'];
        $missing = Validator::require($data, ['to_agency', 'immobile_id']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->createRequest($data);
        Response::json(['id' => $id], 201);
    }

    public function sendMessage(array $request): void
    {
        $data = $request['body'];
        $data['from_agency'] = $request['user']['agency_id'];
        $missing = Validator::require($data, ['request_id', 'to_agency', 'testo']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->sendMessage($data);
        Response::json(['id' => $id], 201);
    }

    public function listMessages(array $request): void
    {
        $requestId = (int)$request['query']['request_id'];
        Response::json($this->model->listMessages($requestId));
    }
}
