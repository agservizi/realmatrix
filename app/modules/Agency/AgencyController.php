<?php
namespace App\Modules\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\JWT;
use App\Core\Database;
use App\Core\Permissions;
use App\Core\Response;

class AgencyController extends Controller
{
    private AgencyModel $model;
    private Auth $auth;

    public function __construct(array $config, Database $db, JWT $jwt)
    {
        parent::__construct($config);
        $this->model = new AgencyModel($db);
        $this->auth = new Auth($db, $jwt);
    }

    public function register(array $request): void
    {
        $body = $request['body'];
        $required = ['name', 'email', 'admin_name', 'admin_email', 'admin_password'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                Response::json(['error' => 'Missing field ' . $field], 422);
                return;
            }
        }

        $result = $this->model->registerAgency($body);
        $token = $this->auth->attemptLogin($body['admin_email'], $body['admin_password']);
        Response::json([
            'agency_id' => $result['agency_id'],
            'admin_user_id' => $result['user_id'],
            'auth' => $token,
        ], 201);
    }
}
