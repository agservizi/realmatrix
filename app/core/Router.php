<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $path = rtrim($path, '/') ?: '/';
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function addMiddleware(string $name, callable $middleware): void
    {
        $this->middlewares[$name] = $middleware;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[\\w-]+)', $route['path']) . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw, true);
                $body = is_array($decoded) ? $decoded : ($_POST ?: []);
                $request = [
                    'method' => $method,
                    'uri' => $uri,
                    'params' => $params,
                    'body' => $body,
                    'query' => $_GET,
                    'headers' => function_exists('getallheaders') ? (getallheaders() ?: []) : [],
                    'files' => $_FILES,
                    'server' => $_SERVER,
                ];

                $pipeline = $this->buildPipeline($route['middlewares']);
                $pipeline($request, $route['handler']);
                return;
            }
        }

        Response::json(['error' => 'Not Found'], 404);
    }

    private function buildPipeline(array $middlewareNames): callable
    {
        $stack = array_reverse($middlewareNames);
        $next = function ($request, $handler) {
            return $handler($request);
        };

        foreach ($stack as $name) {
            $middleware = $this->middlewares[$name] ?? null;
            if (!$middleware) {
                continue;
            }
            $next = function ($request, $handler) use ($middleware, $next) {
                return $middleware($request, function ($req) use ($handler, $next) {
                    return $next($req, $handler);
                });
            };
        }

        return function ($request, $handler) use ($next) {
            return $next($request, $handler);
        };
    }
}
