<?php
/**
 * Migration script to add users table to existing database
 * Run this if you have an existing database without the users table
 */

require_once __DIR__ . '/../api/config/Database.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/okayreads.db';

echo "Adding users table to database...\n";

try {
    $db = Database::getInstance();
    
    // Check if users table already exists
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if ($stmt->fetch()) {
        echo "Users table already exists. No migration needed.\n";
        exit(0);
    }
    
    // Create users table
    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    
    // Create indexes
    $db->query("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    
    echo "Users table created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

