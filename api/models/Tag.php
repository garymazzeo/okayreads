<?php
/**
 * Tag Model
 */

require_once __DIR__ . '/Model.php';

class Tag extends Model {
    protected string $table = 'tags';
    
    /**
     * Find or create tag by name
     */
    public function findOrCreateByName(string $name): array {
        $sql = "SELECT * FROM {$this->table} WHERE name = ? LIMIT 1";
        $stmt = Database::getInstance()->query($sql, [$name]);
        $tag = $stmt->fetch();
        
        if ($tag) {
            return $tag;
        }
        
        // Create new tag
        $id = $this->create(['name' => $name]);
        return $this->find($id);
    }
    
    /**
     * Get books with this tag
     */
    public function getBooks(int $tagId): array {
        $sql = "SELECT b.* FROM books b
                INNER JOIN book_tags bt ON b.id = bt.book_id
                WHERE bt.tag_id = ?
                ORDER BY b.title";
        $stmt = Database::getInstance()->query($sql, [$tagId]);
        return $stmt->fetchAll();
    }
}

