<?php
namespace App\Modules\Auth;

use App\Core\Controller;
use App\Core\Auth as AuthService;
use App\Core\Response;
use App\Core\Database;
use App\Core\JWT;
use App\Core\Validator;

class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct(array $config, Database $db, JWT $jwt)
    {
        parent::__construct($config);
        $this->auth = new AuthService($db, $jwt);
    }

    public function login(array $request): void
    {
        $body = $request['body'];
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $missing = Validator::require($body, ['email', 'password']);
        if ($missing) {
            Response::error('Missing required fields', 422, $missing);
            return;
        }
        $result = $this->auth->attemptLogin($email, $password);
        if (!$result) {
            Response::json(['error' => 'Invalid credentials'], 401);
            return;
        }
        $secure = ($this->config['env'] ?? 'local') !== 'local';
        setcookie('rm_token', $result['token'], [
            'expires' => time() + ($this->config['jwt']['expiration'] ?? 3600),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $secure ? 'None' : 'Lax'
        ]);
        Response::json($result);
    }

    public function logout(): void
    {
        $secure = ($this->config['env'] ?? 'local') !== 'local';
        setcookie('rm_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $secure ? 'None' : 'Lax'
        ]);
        Response::json(['ok' => true]);
    }

    public function me(array $request): void
    {
        Response::json($request['user']);
    }
}
