<?php
/**
 * UserBook Controller
 */

class UserBookController {
    private static function getUserId(): int {
        return (int)($_ENV['DEFAULT_USER_ID'] ?? 1);
    }
    
    /**
     * Get user's books
     */
    public static function index(): void {
        $userId = self::getUserId();
        $status = $_GET['status'] ?? null;
        
        $userBookModel = new UserBook();
        $books = $userBookModel->getUserBooks($userId, $status);
        
        Response::success($books);
    }
    
    /**
     * Add book to user's list
     */
    public static function create(): void {
        $userId = self::getUserId();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        if (empty($data['book_id'])) {
            Response::error('book_id is required');
            return;
        }
        
        // Validate status
        $validStatuses = ['to_read', 'reading', 'finished', 'dropped'];
        $status = $data['status'] ?? 'to_read';
        if (!in_array($status, $validStatuses)) {
            Response::error('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
            return;
        }
        
        $userBookModel = new UserBook();
        
        $userBookData = [
            'status' => $status,
            'date_started' => $data['date_started'] ?? null,
            'date_finished' => $data['date_finished'] ?? null,
            'rating' => !empty($data['rating']) ? (int)$data['rating'] : null,
            'review' => $data['review'] ?? null,
        ];
        
        // Validate rating
        if ($userBookData['rating'] !== null && ($userBookData['rating'] < 1 || $userBookData['rating'] > 5)) {
            Response::error('Rating must be between 1 and 5');
            return;
        }
        
        $userBookId = $userBookModel->addOrUpdate($userId, (int)$data['book_id'], $userBookData);
        
        $userBook = $userBookModel->find($userBookId);
        Response::success($userBook, 'Book added to your list', 201);
    }
    
    /**
     * Update user book
     */
    public static function update(string $id): void {
        $userId = self::getUserId();
        $userBookModel = new UserBook();
        
        $userBook = $userBookModel->find((int)$id);
        
        if (!$userBook || $userBook['user_id'] != $userId) {
            Response::notFound('User book not found');
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        // Validate status if provided
        if (isset($data['status'])) {
            $validStatuses = ['to_read', 'reading', 'finished', 'dropped'];
            if (!in_array($data['status'], $validStatuses)) {
                Response::error('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
                return;
            }
        }
        
        // Validate rating if provided
        if (isset($data['rating']) && $data['rating'] !== null) {
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                Response::error('Rating must be between 1 and 5');
                return;
            }
        }
        
        $updateData = [];
        $allowedFields = ['status', 'date_started', 'date_finished', 'rating', 'review'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (!empty($updateData)) {
            $userBookModel->update((int)$id, $updateData);
        }
        
        $userBook = $userBookModel->find((int)$id);
        Response::success($userBook, 'Book updated successfully');
    }
    
    /**
     * Delete user book
     */
    public static function delete(string $id): void {
        $userId = self::getUserId();
        $userBookModel = new UserBook();
        
        $userBook = $userBookModel->find((int)$id);
        
        if (!$userBook || $userBook['user_id'] != $userId) {
            Response::notFound('User book not found');
            return;
        }
        
        $userBookModel->delete((int)$id);
        Response::success([], 'Book removed from your list');
    }
}

