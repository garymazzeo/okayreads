<?php
/**
 * API Routes
 * This file defines all API routes and their handlers
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Model.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Author.php';
require_once __DIR__ . '/../models/UserBook.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../controllers/BookController.php';
require_once __DIR__ . '/../controllers/UserBookController.php';
require_once __DIR__ . '/../controllers/AuthorController.php';
require_once __DIR__ . '/../controllers/TagController.php';
require_once __DIR__ . '/../controllers/ImportController.php';
require_once __DIR__ . '/../controllers/ExportController.php';


/**
 * Setup routes
 */
function setupRoutes(Router $router): void {
    // Books
    $router->get('/api/books', function() {
        BookController::index();
    });
    
    $router->get('/api/books/search', function() {
        BookController::search();
    });
    
    $router->get('/api/books/{id}', function($params) {
        BookController::show($params['id']);
    });
    
    $router->post('/api/books', function() {
        BookController::create();
    });
    
    $router->put('/api/books/{id}', function($params) {
        BookController::update($params['id']);
    });
    
    $router->delete('/api/books/{id}', function($params) {
        BookController::delete($params['id']);
    });
    
    // User Books
    $router->get('/api/user-books', function() {
        UserBookController::index();
    });
    
    $router->post('/api/user-books', function() {
        UserBookController::create();
    });
    
    $router->put('/api/user-books/{id}', function($params) {
        UserBookController::update($params['id']);
    });
    
    $router->delete('/api/user-books/{id}', function($params) {
        UserBookController::delete($params['id']);
    });
    
    // Authors
    $router->get('/api/authors', function() {
        AuthorController::index();
    });
    
    $router->get('/api/authors/{id}', function($params) {
        AuthorController::show($params['id']);
    });
    
    // Tags
    $router->get('/api/tags', function() {
        TagController::index();
    });
    
    $router->post('/api/books/{id}/tags', function($params) {
        TagController::addToBook($params['id']);
    });
    
    // Import/Export
    $router->post('/api/import/goodreads', function() {
        ImportController::goodreads();
    });
    
    $router->post('/api/import/isbn-list', function() {
        ImportController::isbnList();
    });
    
    $router->get('/api/export/csv', function() {
        ExportController::csv();
    });
}

