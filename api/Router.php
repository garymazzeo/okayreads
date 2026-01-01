<?php
/**
 * Simple Router class
 * Handles request routing and method dispatching
 */

class Router {
    private array $routes = [];
    
    /**
     * Add a route
     */
    public function add(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    /**
     * Add GET route
     */
    public function get(string $path, callable $handler): void {
        $this->add('GET', $path, $handler);
    }
    
    /**
     * Add POST route
     */
    public function post(string $path, callable $handler): void {
        $this->add('POST', $path, $handler);
    }
    
    /**
     * Add PUT route
     */
    public function put(string $path, callable $handler): void {
        $this->add('PUT', $path, $handler);
    }
    
    /**
     * Add DELETE route
     */
    public function delete(string $path, callable $handler): void {
        $this->add('DELETE', $path, $handler);
    }
    
    /**
     * Dispatch request
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getRequestUri();
        
        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            http_response_code(200);
            exit;
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['path'], $uri, $params)) {
                try {
                    call_user_func($route['handler'], $params);
                    return;
                } catch (Exception $e) {
                    Response::error($e->getMessage(), 500);
                    return;
                }
            }
        }
        
        // No route matched
        Response::notFound('Route not found');
    }
    
    /**
     * Match route pattern with URI
     */
    private function matchRoute(string $pattern, string $uri, array &$params): bool {
        // Extract parameter names from pattern
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $paramNames);
        
        // Convert route pattern to regex
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            $params = [];
            for ($i = 1; $i < count($matches); $i++) {
                $paramName = $paramNames[1][$i - 1] ?? "param$i";
                $params[$paramName] = $matches[$i];
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get request URI
     */
    private function getRequestUri(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri;
    }
}

