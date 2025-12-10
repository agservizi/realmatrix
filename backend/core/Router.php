<?php

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function dispatch(string $method, string $uri)
    {
        $path = parse_url($uri, PHP_URL_PATH);
        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            $pattern = '@^' . preg_replace('@\{([^/]+)\}@', '(?P<$1>[^/]+)', $routePath) . '$@';
            if (strtoupper($method) === strtoupper($routeMethod) && preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return call_user_func($handler, $params);
            }
        }
        Response::error('Not Found', 404);
    }
}
