<?php
namespace App\Core;

class Middleware
{
    public static function auth(Auth $auth): callable
    {
        return function ($request, $next) use ($auth) {
            $headers = $request['headers'] ?? [];
            $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
            $cookieToken = $_COOKIE['rm_token'] ?? '';
            if (str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                $user = $auth->validateToken($token);
                if ($user) {
                    $request['user'] = $user;
                    return $next($request);
                }
            }
            if ($cookieToken) {
                $user = $auth->validateToken($cookieToken);
                if ($user) {
                    $request['user'] = $user;
                    return $next($request);
                }
            }
            $accept = $request['headers']['Accept'] ?? ($request['headers']['accept'] ?? '');
            $wantsHtml = ($request['method'] ?? '') === 'GET' && str_contains($accept, 'text/html');
            if ($wantsHtml) {
                header('Location: /login');
                return;
            }
            Response::json(['error' => 'Unauthorized'], 401);
        };
    }

    public static function permission(string $permission): callable
    {
        return function ($request, $next) use ($permission) {
            $claims = $request['user'] ?? [];
            $userPerms = $claims['permissions'] ?? [];
            if (Permissions::hasPermission($userPerms, $permission) || ($claims['role'] ?? '') === 'admin') {
                return $next($request);
            }
            Response::json(['error' => 'Forbidden'], 403);
        };
    }

    public static function agencyScope(): callable
    {
        return function ($request, $next) {
            if (!isset($request['user']['agency_id'])) {
                Response::json(['error' => 'Agency scope missing'], 401);
                return;
            }
            return $next($request);
        };
    }

    public static function rateLimit(int $maxRequests, int $perSeconds): callable
    {
        return function ($request, $next) use ($maxRequests, $perSeconds) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $key = 'rate_' . $ip;
            $now = time();
            $window = $_SESSION[$key]['window'] ?? $now;
            $count = $_SESSION[$key]['count'] ?? 0;
            if ($now - $window > $perSeconds) {
                $window = $now;
                $count = 0;
            }
            $count++;
            $_SESSION[$key] = ['window' => $window, 'count' => $count];
            if ($count > $maxRequests) {
                Response::json(['error' => 'Too Many Requests'], 429);
                return;
            }
            return $next($request);
        };
    }

    public static function csrf(string $secret): callable
    {
        return function ($request, $next) use ($secret) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                return $next($request);
            }
            $token = $_POST['csrf_token'] ?? ($request['headers']['X-CSRF'] ?? '');
            if (!$token || !hash_equals(hash_hmac('sha256', session_id(), $secret), $token)) {
                Response::json(['error' => 'Invalid CSRF token'], 419);
                return;
            }
            return $next($request);
        };
    }
}
