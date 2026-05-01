<?php

class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = $basePath;
    }

    // Register a GET route
    public function get(string $path, callable|array $handler): void {
        $this->routes[] = [
            'method'  => 'GET',
            'path'    => $path,
            'handler' => $handler
        ];
    }

    // Register a POST route
    public function post(string $path, callable|array $handler): void {
        $this->routes[] = [
            'method'  => 'POST',
            'path'    => $path,
            'handler' => $handler
        ];
    }

    // Match and dispatch the current request
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // Remove the full match, keep named captures
                array_shift($matches);
                $this->callHandler($route['handler'], $matches);
                return;
            }
        }

        // No route matched
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'path' => $uri]);
    }

    // Convert /patient/:id  →  regex
    private function convertToRegex(string $path): string {
        $pattern = preg_replace('/\/:([a-zA-Z]+)/', '/(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    // Call the handler — works with closures and [Controller, 'method'] arrays
    private function callHandler(callable|array $handler, array $params): void {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        }
    }
}