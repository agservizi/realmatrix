<?php
namespace App\Core;

use DateTimeImmutable;
use Exception;

class Auth
{
    private Database $db;
    private JWT $jwt;

    public function __construct(Database $database, JWT $jwt)
    {
        $this->db = $database;
        $this->jwt = $jwt;
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        $perms = $user['permissions'] ? json_decode($user['permissions'], true) : [];
        $payload = [
            'sub' => $user['id'],
            'agency_id' => $user['agency_id'],
            'role' => $user['role'],
            'email' => $user['email'],
            'permissions' => $perms,
        ];
        $token = $this->jwt->encode($payload);
        return ['token' => $token, 'user' => $this->publicUser($user)];
    }

    public function validateToken(string $token): ?array
    {
        try {
            return $this->jwt->decode($token);
        } catch (Exception $e) {
            return null;
        }
    }

    public function publicUser(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
