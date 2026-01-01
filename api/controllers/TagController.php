<?php
/**
 * Tag Controller
 */

class TagController {
    /**
     * Get all tags
     */
    public static function index(): void {
        $tagModel = new Tag();
        $tags = $tagModel->findAll([], 'name');
        
        Response::success($tags);
    }
    
    /**
     * Add tag to book
     */
    public static function addToBook(string $bookId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            Response::error('Invalid JSON data');
            return;
        }
        
        if (empty($data['tag_id']) && empty($data['tag_name'])) {
            Response::error('tag_id or tag_name is required');
            return;
        }
        
        $bookModel = new Book();
        $tagModel = new Tag();
        
        $book = $bookModel->find((int)$bookId);
        if (!$book) {
            Response::notFound('Book not found');
            return;
        }
        
        // Get or create tag
        if (!empty($data['tag_id'])) {
            $tag = $tagModel->find((int)$data['tag_id']);
            if (!$tag) {
                Response::notFound('Tag not found');
                return;
            }
            $tagId = $tag['id'];
        } else {
            $tag = $tagModel->findOrCreateByName($data['tag_name']);
            $tagId = $tag['id'];
        }
        
        $bookModel->addTag((int)$bookId, $tagId);
        
        $book = $bookModel->findWithAuthors((int)$bookId);
        Response::success($book, 'Tag added successfully');
    }
}

