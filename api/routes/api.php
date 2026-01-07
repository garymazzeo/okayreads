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
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../controllers/AuthController.php';
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
    // Authentication (public)
    $router->post('/api/auth/register', function() {
        AuthController::register();
    });
    
    $router->post('/api/auth/login', function() {
        AuthController::login();
    });
    
    $router->post('/api/auth/logout', function() {
        AuthController::logout();
    });
    
    $router->get('/api/auth/me', function() {
        AuthController::me();
    });
    
    // Books (public read, protected write)
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
        AuthController::requireAuth();
        BookController::create();
    });
    
    $router->put('/api/books/{id}', function($params) {
        AuthController::requireAuth();
        BookController::update($params['id']);
    });
    
    $router->delete('/api/books/{id}', function($params) {
        AuthController::requireAuth();
        BookController::delete($params['id']);
    });
    
    // User Books (protected)
    $router->get('/api/user-books', function() {
        AuthController::requireAuth();
        UserBookController::index();
    });
    
    $router->post('/api/user-books', function() {
        AuthController::requireAuth();
        UserBookController::create();
    });
    
    $router->put('/api/user-books/{id}', function($params) {
        AuthController::requireAuth();
        UserBookController::update($params['id']);
    });
    
    $router->delete('/api/user-books/{id}', function($params) {
        AuthController::requireAuth();
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
        AuthController::requireAuth();
        TagController::addToBook($params['id']);
    });
    
    // Import/Export (protected)
    $router->post('/api/import/goodreads', function() {
        AuthController::requireAuth();
        ImportController::goodreads();
    });
    
    $router->post('/api/import/isbn-list', function() {
        AuthController::requireAuth();
        ImportController::isbnList();
    });
    
    $router->get('/api/export/csv', function() {
        AuthController::requireAuth();
        ExportController::csv();
    });
}

