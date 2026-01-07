<?php
/**
 * Database connection class
 * Singleton pattern for connection reuse
 * Supports both SQLite and MySQL via PDO
 */

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void {
        $dbType = $_ENV['DB_TYPE'] ?? 'sqlite';
        
        if ($dbType === 'sqlite') {
            $dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../../database/okayreads.db';
            
            // Ensure directory exists
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $dsn = 'sqlite:' . $dbPath;
            
            try {
                $this->connection = new PDO($dsn);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Auto-initialize database if needed
                $this->initializeDatabase();
                
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        } elseif ($dbType === 'mysql') {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dbName = $_ENV['DB_NAME'] ?? 'okayreads';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
            
            try {
                $this->connection = new PDO($dsn, $user, $pass);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        } else {
            throw new Exception("Unsupported database type: $dbType");
        }
    }
    
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Initialize database schema if tables don't exist
     */
    private function initializeDatabase(): void {
        try {
            // Check if books table exists (indicates if DB is initialized)
            $stmt = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='books'");
            if ($stmt->fetch() === false) {
                // Database is empty, run schema
                $schemaFile = __DIR__ . '/../../database/schema.sql';
                if (file_exists($schemaFile)) {
                    $schema = file_get_contents($schemaFile);
                    $this->connection->exec($schema);
                }
            } else {
                // Check if users table exists (might be missing if DB was created before auth was added)
                $stmt = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                if ($stmt->fetch() === false) {
                    // Users table is missing, add it
                    $this->connection->exec("CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        username TEXT NOT NULL UNIQUE,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        created_at TEXT NOT NULL DEFAULT (datetime('now')),
                        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
                    )");
                    $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
                    $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
                }
            }
        } catch (PDOException $e) {
            // Log but don't throw - allows app to continue even if schema issues exist
            error_log('Database initialization warning: ' . $e->getMessage());
        }
    }
}

