<?php
/**
 * Authentication Controller
 */

class AuthController {
    /**
     * Register new user
     */
    public static function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            Response::error('Username, email, and password are required');
            return;
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
            return;
        }
        
        // Validate password length
        if (strlen($data['password']) < 6) {
            Response::error('Password must be at least 6 characters');
            return;
        }
        
        $userModel = new User();
        
        // Check if username already exists
        if ($userModel->findByUsername($data['username'])) {
            Response::error('Username already taken');
            return;
        }
        
        // Check if email already exists
        if ($userModel->findByEmail($data['email'])) {
            Response::error('Email already registered');
            return;
        }
        
        try {
            $userId = $userModel->create([
                'username' => trim($data['username']),
                'email' => trim($data['email']),
                'password' => $data['password']
            ]);
            
            // Auto-login after registration
            $user = $userModel->find($userId);
            self::setSession($user);
            
            Response::success($userModel->getSafe($user), 'Registration successful');
        } catch (Exception $e) {
            Response::error('Registration failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Login
     */
    public static function login(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        if (empty($data['username']) || empty($data['password'])) {
            Response::error('Username and password are required');
            return;
        }
        
        $userModel = new User();
        
        // Try to find by username or email
        $user = $userModel->findByUsername($data['username']);
        if (!$user) {
            $user = $userModel->findByEmail($data['username']);
        }
        
        if (!$user) {
            Response::error('Invalid username or password');
            return;
        }
        
        // Verify password
        if (!$userModel->verifyPassword($data['password'], $user['password_hash'])) {
            Response::error('Invalid username or password');
            return;
        }
        
        // Set session
        self::setSession($user);
        
        Response::success($userModel->getSafe($user), 'Login successful');
    }
    
    /**
     * Logout
     */
    public static function logout(): void {
        self::destroySession();
        Response::success([], 'Logout successful');
    }
    
    /**
     * Get current user
     */
    public static function me(): void {
        $user = self::getCurrentUser();
        
        if (!$user) {
            Response::error('Not authenticated', 401);
            return;
        }
        
        $userModel = new User();
        Response::success($userModel->getSafe($user));
    }
    
    /**
     * Set session
     */
    private static function setSession(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
    }
    
    /**
     * Destroy session
     */
    private static function destroySession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
    }
    
    /**
     * Get current user from session
     */
    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->find($_SESSION['user_id']);
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool {
        return self::getCurrentUser() !== null;
    }
    
    /**
     * Require authentication (middleware)
     */
    public static function requireAuth(): void {
        if (!self::isAuthenticated()) {
            Response::error('Authentication required', 401);
            exit;
        }
    }
}

