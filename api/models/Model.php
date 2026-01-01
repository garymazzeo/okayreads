<?php
/**
 * Base Model class
 * Provides common CRUD operations for all models
 */

abstract class Model {
    protected string $table;
    protected string $primaryKey = 'id';
    protected Database $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find a record by ID
     */
    public function find(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find all records with optional conditions
     */
    public function findAll(array $conditions = [], string $orderBy = '', int $limit = 0, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new record
     */
    public function create(array $data): int {
        // Add timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->query($sql, array_values($data));
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update a record
     */
    public function update(int $id, array $data): bool {
        // Add updated_at timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $fields = [];
        $params = [];
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $params[] = $value;
        }
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete a record
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = []): int {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
}

