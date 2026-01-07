<?php
/**
 * User Model
 */

class User extends Model {
    protected string $table = 'users';
    
    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array {
        $stmt = Database::getInstance()->query(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array {
        $stmt = Database::getInstance()->query(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Create new user
     */
    public function create(array $data): int {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        return parent::create($data);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Get user without password hash
     */
    public function getSafe(array $user): array {
        unset($user['password_hash']);
        return $user;
    }
}

