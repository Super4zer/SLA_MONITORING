<?php

namespace App\Routing;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void
    {
        // Convert path with parameters like /api/monitoring/{id}/resolve
        // to a regular expression like /^\/api\/monitoring\/(?P<id>[^\/]+)\/resolve$/
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
        $pattern = "#^{$pattern}$#";

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        // Strip query string if any
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];

                try {
                    if (is_array($handler) && is_string($handler[0])) {
                        $controller = new $handler[0]();
                        $handler[0] = $controller;
                    }
                    
                    $response = call_user_func_array($handler, $params);
                    if (is_array($response) || is_object($response)) {
                        header('Content-Type: application/json');
                        echo json_encode($response);
                    } elseif (is_string($response)) {
                        echo $response;
                    }
                } catch (\Throwable $e) {
                    $this->sendError(500, "Internal Server Error: " . $e->getMessage());
                }
                return;
            }
        }

        $this->sendError(404, "Not Found");
    }

    private function sendError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }
}
