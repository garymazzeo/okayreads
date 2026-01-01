<?php
/**
 * Book Controller
 */

class BookController {
    /**
     * Get all books with optional filters
     */
    public static function index(): void {
        $bookModel = new Book();
        
        $query = $_GET['search'] ?? '';
        $authorId = $_GET['author_id'] ?? null;
        $tagId = $_GET['tag_id'] ?? null;
        
        $filters = [];
        if ($authorId) {
            $filters['author_id'] = (int)$authorId;
        }
        if ($tagId) {
            $filters['tag_id'] = (int)$tagId;
        }
        
        if (!empty($query) || !empty($filters)) {
            $books = $bookModel->search($query, $filters);
        } else {
            $books = $bookModel->findAll([], 'title');
        }
        
        // Add authors and tags to each book
        foreach ($books as &$book) {
            $book['authors'] = $bookModel->getAuthors($book['id']);
            $book['tags'] = $bookModel->getTags($book['id']);
        }
        
        Response::success($books);
    }
    
    /**
     * Get single book
     */
    public static function show(string $id): void {
        $bookModel = new Book();
        $book = $bookModel->findWithAuthors((int)$id);
        
        if (!$book) {
            Response::notFound('Book not found');
            return;
        }
        
        Response::success($book);
    }
    
    /**
     * Create book
     */
    public static function create(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        // Validate required fields
        if (empty($data['title'])) {
            Response::error('Title is required');
            return;
        }
        
        $bookModel = new Book();
        $authorModel = new Author();
        
        // Prepare book data
        $bookData = [
            'title' => $data['title'],
            'isbn' => $data['isbn'] ?? null,
            'description' => $data['description'] ?? null,
            'page_count' => !empty($data['page_count']) ? (int)$data['page_count'] : null,
            'published_date' => $data['published_date'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
        ];
        
        // Create book
        $bookId = $bookModel->create($bookData);
        
        // Handle authors
        if (!empty($data['authors'])) {
            $authorIds = [];
            foreach ($data['authors'] as $authorName) {
                if (is_string($authorName)) {
                    $author = $authorModel->findOrCreateByName($authorName);
                    $authorIds[] = $author['id'];
                } elseif (is_int($authorName)) {
                    $authorIds[] = $authorName;
                }
            }
            if (!empty($authorIds)) {
                $bookModel->setAuthors($bookId, $authorIds);
            }
        }
        
        $book = $bookModel->findWithAuthors($bookId);
        Response::success($book, 'Book created successfully', 201);
    }
    
    /**
     * Update book
     */
    public static function update(string $id): void {
        $bookModel = new Book();
        $book = $bookModel->find((int)$id);
        
        if (!$book) {
            Response::notFound('Book not found');
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        // Prepare update data
        $updateData = [];
        $allowedFields = ['title', 'isbn', 'description', 'page_count', 'published_date', 'cover_image_url'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (!empty($updateData)) {
            $bookModel->update((int)$id, $updateData);
        }
        
        // Handle authors if provided
        if (isset($data['authors'])) {
            $authorModel = new Author();
            $authorIds = [];
            foreach ($data['authors'] as $authorName) {
                if (is_string($authorName)) {
                    $author = $authorModel->findOrCreateByName($authorName);
                    $authorIds[] = $author['id'];
                } elseif (is_int($authorName)) {
                    $authorIds[] = $authorName;
                }
            }
            $bookModel->setAuthors((int)$id, $authorIds);
        }
        
        $book = $bookModel->findWithAuthors((int)$id);
        Response::success($book, 'Book updated successfully');
    }
    
    /**
     * Delete book
     */
    public static function delete(string $id): void {
        $bookModel = new Book();
        $book = $bookModel->find((int)$id);
        
        if (!$book) {
            Response::notFound('Book not found');
            return;
        }
        
        $bookModel->delete((int)$id);
        Response::success([], 'Book deleted successfully');
    }
    
    /**
     * Search books online
     */
    public static function search(): void {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            Response::error('Search query is required');
            return;
        }
        
        require_once __DIR__ . '/../services/BookSearchService.php';
        $searchService = new BookSearchService();
        
        try {
            $results = $searchService->search($query);
            Response::success($results);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}

