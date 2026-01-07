<?php
/**
 * API Entry Point
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Set to '1' for development

// Start output buffering to catch any accidental output
ob_start();

// Set error handler to return JSON
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() === 0) {
        return false;
    }
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $message
    ]);
    exit;
}, E_ALL & ~E_NOTICE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
// Try parent directory first (more secure), then current directory
$envFile = __DIR__ . '/../../config/okayreads/.env';  // Parent of project root
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';  // Fallback to project root
}
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
// Set defaults
if (!isset($_ENV['DB_TYPE'])) $_ENV['DB_TYPE'] = 'sqlite';
if (!isset($_ENV['DB_PATH'])) $_ENV['DB_PATH'] = __DIR__ . '/../database/okayreads.db';
if (!isset($_ENV['DEFAULT_USER_ID'])) $_ENV['DEFAULT_USER_ID'] = '1';

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/config/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/services/' . $class . '.php',
        __DIR__ . '/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/routes/api.php';

// Initialize router
try {
    $router = new Router();
    setupRoutes($router);
    $router->dispatch();
} catch (Throwable $e) {
    ob_clean();
    error_log('Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred'
    ]);
    exit;
}

