<?php
/**
 * UserBook Model (reading status tracking)
 */

require_once __DIR__ . '/Model.php';

class UserBook extends Model {
    protected string $table = 'user_books';
    
    /**
     * Get user's books with book details
     */
    public function getUserBooks(int $userId, ?string $status = null): array {
        $sql = "SELECT ub.*, b.* FROM {$this->table} ub
                INNER JOIN books b ON ub.book_id = b.id";
        $params = [$userId];
        $conditions = ["ub.user_id = ?"];
        
        if ($status) {
            $conditions[] = "ub.status = ?";
            $params[] = $status;
        }
        
        $sql .= " WHERE " . implode(" AND ", $conditions);
        $sql .= " ORDER BY ub.updated_at DESC";
        
        $stmt = Database::getInstance()->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user book by book ID
     */
    public function findByUserAndBook(int $userId, int $bookId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND book_id = ? LIMIT 1";
        $stmt = Database::getInstance()->query($sql, [$userId, $bookId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Add or update user book
     */
    public function addOrUpdate(int $userId, int $bookId, array $data): int {
        $existing = $this->findByUserAndBook($userId, $bookId);
        
        if ($existing) {
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            $data['user_id'] = $userId;
            $data['book_id'] = $bookId;
            return $this->create($data);
        }
    }
    
    /**
     * Get reading statistics
     */
    public function getStats(int $userId): array {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} WHERE user_id = ? GROUP BY status";
        $stmt = Database::getInstance()->query($sql, [$userId]);
        $results = $stmt->fetchAll();
        
        $stats = [
            'to_read' => 0,
            'reading' => 0,
            'finished' => 0,
            'dropped' => 0,
            'total' => 0
        ];
        
        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }
        
        return $stats;
    }
}

