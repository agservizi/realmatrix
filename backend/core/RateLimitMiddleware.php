<?php

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Response.php';

class RateLimitMiddleware
{
    public static function handle(string $key): void
    {
        $max = (int)Env::get('RATE_LIMIT_REQUESTS', 20);
        $window = (int)Env::get('RATE_LIMIT_WINDOW', 60);
        $limiter = new RateLimiter(__DIR__ . '/../storage/ratelimit');
        if (!$limiter->hit($key, $max, $window)) {
            Response::error('Too Many Requests', 429);
        }
    }
}
