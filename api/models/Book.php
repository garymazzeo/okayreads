<?php
/**
 * Book Model
 */

require_once __DIR__ . '/Model.php';

class Book extends Model {
    protected string $table = 'books';
    
    /**
     * Get book with authors
     */
    public function findWithAuthors(int $id): ?array {
        $book = $this->find($id);
        if (!$book) {
            return null;
        }
        
        $book['authors'] = $this->getAuthors($id);
        $book['tags'] = $this->getTags($id);
        
        return $book;
    }
    
    /**
     * Get authors for a book
     */
    public function getAuthors(int $bookId): array {
        $sql = "SELECT a.* FROM authors a
                INNER JOIN book_authors ba ON a.id = ba.author_id
                WHERE ba.book_id = ?
                ORDER BY a.name";
        $stmt = Database::getInstance()->query($sql, [$bookId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tags for a book
     */
    public function getTags(int $bookId): array {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN book_tags bt ON t.id = bt.tag_id
                WHERE bt.book_id = ?
                ORDER BY t.name";
        $stmt = Database::getInstance()->query($sql, [$bookId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Set authors for a book
     */
    public function setAuthors(int $bookId, array $authorIds): void {
        $db = Database::getInstance();
        
        // Remove existing relationships
        $db->query("DELETE FROM book_authors WHERE book_id = ?", [$bookId]);
        
        // Add new relationships
        foreach ($authorIds as $authorId) {
            $db->query("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)", [$bookId, $authorId]);
        }
    }
    
    /**
     * Add tag to book
     */
    public function addTag(int $bookId, int $tagId): void {
        $db = Database::getInstance();
        
        // Check if relationship already exists
        $stmt = $db->query("SELECT COUNT(*) as count FROM book_tags WHERE book_id = ? AND tag_id = ?", [$bookId, $tagId]);
        $result = $stmt->fetch();
        
        if ((int)($result['count'] ?? 0) === 0) {
            $db->query("INSERT INTO book_tags (book_id, tag_id) VALUES (?, ?)", [$bookId, $tagId]);
        }
    }
    
    /**
     * Search books
     */
    public function search(string $query, array $filters = []): array {
        $sql = "SELECT DISTINCT b.* FROM {$this->table} b";
        $params = [];
        $conditions = [];
        
        // Search in title and description
        if (!empty($query)) {
            $conditions[] = "(b.title LIKE ? OR b.description LIKE ?)";
            $searchTerm = "%$query%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filter by author
        if (!empty($filters['author_id'])) {
            $sql .= " INNER JOIN book_authors ba ON b.id = ba.book_id";
            $conditions[] = "ba.author_id = ?";
            $params[] = $filters['author_id'];
        }
        
        // Filter by tag
        if (!empty($filters['tag_id'])) {
            $sql .= " INNER JOIN book_tags bt ON b.id = bt.book_id";
            $conditions[] = "bt.tag_id = ?";
            $params[] = $filters['tag_id'];
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY b.title";
        
        $stmt = Database::getInstance()->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find by ISBN
     */
    public function findByIsbn(string $isbn): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE isbn = ? LIMIT 1";
        $stmt = Database::getInstance()->query($sql, [$isbn]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}

