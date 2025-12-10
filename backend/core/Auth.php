<?php

require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Env.php';

class Auth
{
    public static function issueTokens(array $user, array $permissions): array
    {
        $accessTtl = (int)Env::get('JWT_TTL', 86400);
        $refreshTtl = (int)Env::get('JWT_REFRESH_TTL', 604800);
        $base = [
            'user_id' => $user['id'],
            'agency_id' => $user['agency_id'],
            'role' => $user['role'],
            'permissions' => $permissions
        ];
        $access = JWT::encode(array_merge($base, ['type' => 'access']), $accessTtl);
        $refresh = JWT::encode(array_merge($base, ['type' => 'refresh']), $refreshTtl);
        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'expires_in' => $accessTtl
        ];
    }

    public static function user(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($header, 'Bearer ') !== 0) {
            return null;
        }
        $token = substr($header, 7);
        $payload = JWT::decode($token);
        if (!$payload || ($payload['type'] ?? 'access') !== 'access') {
            return null;
        }
        return $payload;
    }

    public static function requireUser(): array
    {
        $payload = self::user();
        if (!$payload) {
            Response::error('Unauthorized', 401);
        }
        return $payload;
    }

    public static function refresh(string $refreshToken): ?array
    {
        $payload = JWT::decode($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }
        return self::issueTokens([
            'id' => $payload['user_id'],
            'agency_id' => $payload['agency_id'],
            'role' => $payload['role']
        ], $payload['permissions'] ?? []);
    }
}
