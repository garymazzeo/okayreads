<?php
/**
 * Author Controller
 */

class AuthorController {
    /**
     * Get all authors
     */
    public static function index(): void {
        $authorModel = new Author();
        $authors = $authorModel->findAll([], 'name');
        
        Response::success($authors);
    }
    
    /**
     * Get single author with books
     */
    public static function show(string $id): void {
        $authorModel = new Author();
        $author = $authorModel->findWithBooks((int)$id);
        
        if (!$author) {
            Response::notFound('Author not found');
            return;
        }
        
        Response::success($author);
    }
}

