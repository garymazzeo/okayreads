<?php
/**
 * Author Model
 */

require_once __DIR__ . '/Model.php';

class Author extends Model {
    protected string $table = 'authors';
    
    /**
     * Get author with books
     */
    public function findWithBooks(int $id): ?array {
        $author = $this->find($id);
        if (!$author) {
            return null;
        }
        
        $author['books'] = $this->getBooks($id);
        
        return $author;
    }
    
    /**
     * Get books by author
     */
    public function getBooks(int $authorId): array {
        $sql = "SELECT b.* FROM books b
                INNER JOIN book_authors ba ON b.id = ba.book_id
                WHERE ba.author_id = ?
                ORDER BY b.title";
        $stmt = Database::getInstance()->query($sql, [$authorId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Find or create author by name
     */
    public function findOrCreateByName(string $name): array {
        $sql = "SELECT * FROM {$this->table} WHERE name = ? LIMIT 1";
        $stmt = Database::getInstance()->query($sql, [$name]);
        $author = $stmt->fetch();
        
        if ($author) {
            return $author;
        }
        
        // Create new author
        $id = $this->create(['name' => $name]);
        return $this->find($id);
    }
}

