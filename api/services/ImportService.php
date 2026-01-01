<?php
/**
 * Import Service
 * Handles importing books from Goodreads CSV and ISBN lists
 */

require_once __DIR__ . '/BookSearchService.php';

class ImportService {
    private BookSearchService $searchService;
    private Book $bookModel;
    private Author $authorModel;
    private UserBook $userBookModel;
    
    public function __construct() {
        $this->searchService = new BookSearchService();
        $this->bookModel = new Book();
        $this->authorModel = new Author();
        $this->userBookModel = new UserBook();
    }
    
    /**
     * Import from Goodreads CSV
     */
    public function importGoodreadsCsv(string $filePath, int $userId = 1): array {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception("Could not open file: $filePath");
        }
        
        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new Exception("Could not read CSV headers");
        }
        
        // Map column indices
        $columnMap = $this->mapGoodreadsColumns($headers);
        
        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            
            try {
                $bookData = $this->parseGoodreadsRow($row, $columnMap);
                
                if (empty($bookData['title'])) {
                    $results['skipped']++;
                    continue;
                }
                
                // Check if book already exists
                $existingBook = null;
                if (!empty($bookData['isbn'])) {
                    $existingBook = $this->bookModel->findByIsbn($bookData['isbn']);
                }
                
                if (!$existingBook) {
                    // Create book
                    $bookId = $this->bookModel->create([
                        'title' => $bookData['title'],
                        'isbn' => $bookData['isbn'],
                        'description' => null,
                        'page_count' => null,
                        'published_date' => null,
                        'cover_image_url' => null,
                    ]);
                } else {
                    $bookId = $existingBook['id'];
                }
                
                // Handle authors
                if (!empty($bookData['authors'])) {
                    $authorIds = [];
                    foreach ($bookData['authors'] as $authorName) {
                        $author = $this->authorModel->findOrCreateByName($authorName);
                        $authorIds[] = $author['id'];
                    }
                    if (!empty($authorIds)) {
                        $this->bookModel->setAuthors($bookId, $authorIds);
                    }
                }
                
                // Add to user's list
                if (!empty($bookData['status'])) {
                    $userBookData = [
                        'status' => $bookData['status'],
                        'date_started' => $bookData['date_started'] ?? null,
                        'date_finished' => $bookData['date_finished'] ?? null,
                        'rating' => !empty($bookData['rating']) ? (int)$bookData['rating'] : null,
                        'review' => $bookData['review'] ?? null,
                    ];
                    
                    $this->userBookModel->addOrUpdate($userId, $bookId, $userBookData);
                }
                
                $results['imported']++;
            } catch (Exception $e) {
                $results['errors'][] = "Line $lineNumber: " . $e->getMessage();
                $results['skipped']++;
            }
        }
        
        fclose($handle);
        return $results;
    }
    
    /**
     * Map Goodreads CSV columns to indices
     */
    private function mapGoodreadsColumns(array $headers): array {
        $map = [];
        $commonMappings = [
            'title' => ['Title', 'Book Title'],
            'author' => ['Author', 'Author l-f', 'Additional Authors'],
            'isbn' => ['ISBN', 'ISBN13'],
            'rating' => ['My Rating', 'Rating'],
            'date_read' => ['Date Read', 'Date Added', 'Date Read String'],
            'review' => ['My Review', 'Review'],
        ];
        
        foreach ($commonMappings as $key => $possibleNames) {
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers);
                if ($index !== false) {
                    $map[$key] = $index;
                    break;
                }
            }
        }
        
        return $map;
    }
    
    /**
     * Parse a Goodreads CSV row
     */
    private function parseGoodreadsRow(array $row, array $columnMap): array {
        $data = [
            'title' => '',
            'authors' => [],
            'isbn' => null,
            'status' => 'to_read',
            'rating' => null,
            'date_started' => null,
            'date_finished' => null,
            'review' => null,
        ];
        
        // Title
        if (isset($columnMap['title'])) {
            $data['title'] = trim($row[$columnMap['title']] ?? '');
        }
        
        // Authors
        if (isset($columnMap['author'])) {
            $authorStr = trim($row[$columnMap['author']] ?? '');
            if (!empty($authorStr)) {
                $data['authors'] = array_map('trim', explode(',', $authorStr));
            }
        }
        
        // ISBN
        if (isset($columnMap['isbn'])) {
            $isbn = trim($row[$columnMap['isbn']] ?? '');
            // Remove dashes and spaces
            $isbn = preg_replace('/[-\s]/', '', $isbn);
            if (!empty($isbn)) {
                $data['isbn'] = $isbn;
            }
        }
        
        // Rating
        if (isset($columnMap['rating'])) {
            $rating = trim($row[$columnMap['rating']] ?? '');
            if (is_numeric($rating) && $rating >= 1 && $rating <= 5) {
                $data['rating'] = (int)$rating;
            }
        }
        
        // Date Read
        if (isset($columnMap['date_read'])) {
            $dateStr = trim($row[$columnMap['date_read']] ?? '');
            if (!empty($dateStr)) {
                // Try to parse date
                $timestamp = strtotime($dateStr);
                if ($timestamp !== false) {
                    $data['date_finished'] = date('Y-m-d', $timestamp);
                    $data['status'] = 'finished';
                }
            }
        }
        
        // Review
        if (isset($columnMap['review'])) {
            $data['review'] = trim($row[$columnMap['review']] ?? '');
        }
        
        return $data;
    }
    
    /**
     * Import from ISBN list
     */
    public function importIsbnList(string $filePath, int $userId = 1): array {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $lineNumber => $line) {
            $isbn = trim($line);
            
            if (empty($isbn)) {
                continue;
            }
            
            // Remove dashes and spaces
            $isbn = preg_replace('/[-\s]/', '', $isbn);
            
            try {
                // Check if book already exists
                $existingBook = $this->bookModel->findByIsbn($isbn);
                
                if ($existingBook) {
                    $results['skipped']++;
                    continue;
                }
                
                // Search for book
                $searchResults = $this->searchService->search($isbn);
                
                if (empty($searchResults)) {
                    $results['errors'][] = "ISBN $isbn: No results found";
                    $results['skipped']++;
                    continue;
                }
                
                // Use first result
                $bookData = $searchResults[0];
                
                // Create book
                $bookId = $this->bookModel->create([
                    'title' => $bookData['title'],
                    'isbn' => $isbn,
                    'description' => $bookData['description'] ?? null,
                    'page_count' => $bookData['page_count'] ?? null,
                    'published_date' => $bookData['published_date'] ?? null,
                    'cover_image_url' => $bookData['cover_image_url'] ?? null,
                ]);
                
                // Handle authors
                if (!empty($bookData['authors'])) {
                    $authorIds = [];
                    foreach ($bookData['authors'] as $authorName) {
                        $author = $this->authorModel->findOrCreateByName($authorName);
                        $authorIds[] = $author['id'];
                    }
                    if (!empty($authorIds)) {
                        $this->bookModel->setAuthors($bookId, $authorIds);
                    }
                }
                
                // Add to user's list as "to_read"
                $this->userBookModel->addOrUpdate($userId, $bookId, [
                    'status' => 'to_read'
                ]);
                
                $results['imported']++;
            } catch (Exception $e) {
                $results['errors'][] = "ISBN $isbn: " . $e->getMessage();
                $results['skipped']++;
            }
        }
        
        return $results;
    }
}

