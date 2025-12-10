<?php

require_once __DIR__ . '/Auth.php';

class AuthMiddleware
{
    public static function handle(): array
    {
        return Auth::requireUser();
    }
}
