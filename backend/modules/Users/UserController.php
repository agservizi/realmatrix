<?php

require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/RateLimitMiddleware.php';

class UserController extends Controller
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function login(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        RateLimitMiddleware::handle('login_' . $ip);
        $data = $this->input();
        if (!isset($data['email'], $data['password'])) {
            $this->bad('Missing credentials');
        }
        $user = $this->model->findByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $user['password_hash']) || !$user['active']) {
            $this->bad('Invalid credentials', 401);
        }
        $perms = $this->model->getPermissions((int)$user['id']);
        $tokens = Auth::issueTokens($user, $perms);
        $this->ok([
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'user' => [
                'id' => $user['id'],
                'agency_id' => $user['agency_id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'permissions' => $perms
            ]
        ]);
    }

    public function refresh(): void
    {
        $data = $this->input();
        if (!isset($data['refresh_token'])) {
            $this->bad('Missing refresh_token');
        }
        $tokens = Auth::refresh($data['refresh_token']);
        if (!$tokens) {
            $this->bad('Invalid refresh token', 401);
        }
        $this->ok($tokens);
    }
}
