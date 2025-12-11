<?php
namespace App\Modules\Users;

use App\Core\Controller;
use App\Core\Permissions;
use App\Core\Response;
use App\Core\Database;
use App\Core\Validator;

class UsersController extends Controller
{
    private UsersModel $model;

    public function __construct(array $config, Database $db)
    {
        parent::__construct($config);
        $this->model = new UsersModel($db);
    }

    public function create(array $request): void
    {
        $body = $request['body'];
        $agencyId = $request['user']['agency_id'];
        $missing = Validator::require($body, ['name', 'email', 'password', 'role']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $id = $this->model->createCollaborator($agencyId, $body);
        Response::json(['id' => $id], 201);
    }

    public function list(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $users = $this->model->listCollaborators($agencyId);
        Response::json($users);
    }

    public function update(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $id = (int)$request['params']['id'];
        $body = $request['body'];
        $missing = Validator::require($body, ['role']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $ok = $this->model->updateCollaborator($agencyId, $id, $body);
        Response::json(['updated' => $ok]);
    }

    public function delete(array $request): void
    {
        $agencyId = $request['user']['agency_id'];
        $id = (int)$request['params']['id'];
        $ok = $this->model->deactivateCollaborator($agencyId, $id);
        Response::json(['deleted' => $ok]);
    }

    public function permissions(): void
    {
        Response::json(Permissions::all());
    }
}
