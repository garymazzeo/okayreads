<?php
/**
 * Database initialization script
 * Creates the SQLite database and runs the schema
 */

require_once __DIR__ . '/../api/config/Database.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
} else {
    // Defaults
    $_ENV['DB_TYPE'] = 'sqlite';
    $_ENV['DB_PATH'] = __DIR__ . '/okayreads.db';
}

$dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/okayreads.db';
$schemaFile = __DIR__ . '/schema.sql';

echo "Initializing OkayReads database...\n";

// Ensure directory exists
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Check if database already exists
if (file_exists($dbPath)) {
    echo "Database already exists at: $dbPath\n";
    echo "Delete it first if you want to recreate.\n";
    exit(1);
}

// Read and execute schema
if (!file_exists($schemaFile)) {
    echo "Error: Schema file not found: $schemaFile\n";
    exit(1);
}

$schema = file_get_contents($schemaFile);

try {
    // Create database connection
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Execute schema
    $db->exec($schema);
    
    echo "Database created successfully at: $dbPath\n";
    echo "Schema applied successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

