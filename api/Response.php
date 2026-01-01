<?php
/**
 * Response helper class
 * Handles JSON responses and HTTP status codes
 */

class Response {
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success(array $data = [], string $message = '', int $statusCode = 200): void {
        $response = ['success' => true];
        if ($message) {
            $response['message'] = $message;
        }
        if (!empty($data)) {
            $response['data'] = $data;
        }
        self::json($response, $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): void {
        $response = [
            'success' => false,
            'error' => $message
        ];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        self::json($response, $statusCode);
    }
    
    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error($message, 401);
    }
}

