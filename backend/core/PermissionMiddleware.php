<?php

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Response.php';

class PermissionMiddleware
{
    public static function handle(string $permission): void
    {
        $user = Auth::requireUser();
        $perms = $user['permissions'] ?? [];
        if (!in_array($permission, $perms, true)) {
            Response::error('Forbidden: missing permission ' . $permission, 403);
        }
    }
}
