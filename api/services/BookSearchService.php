<?php
/**
 * Book Search Service
 * Integrates with Google Books API and Open Library API
 */

class BookSearchService {
    private const GOOGLE_BOOKS_API = 'https://www.googleapis.com/books/v1/volumes';
    private const OPEN_LIBRARY_API = 'https://openlibrary.org/search.json';
    private const MAX_RESULTS = 10;
    
    /**
     * Search for books
     */
    public function search(string $query): array {
        // Try Google Books first
        $results = $this->searchGoogleBooks($query);
        
        // Fallback to Open Library if Google Books fails or returns no results
        if (empty($results)) {
            $results = $this->searchOpenLibrary($query);
        }
        
        return $results;
    }
    
    /**
     * Search Google Books API
     */
    private function searchGoogleBooks(string $query): array {
        $url = self::GOOGLE_BOOKS_API . '?q=' . urlencode($query) . '&maxResults=' . self::MAX_RESULTS;
        
        // Try cURL first (more reliable)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'OkayReads/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                error_log("Google Books API error: HTTP $httpCode - $error");
                return [];
            }
        } else {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'user_agent' => 'OkayReads/1.0',
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("Google Books API error: file_get_contents failed");
                return [];
            }
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Google Books API error: JSON decode failed - " . json_last_error_msg());
            return [];
        }
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            return [];
        }
        
        $results = [];
        foreach ($data['items'] as $item) {
            $book = $this->normalizeGoogleBook($item);
            if ($book) {
                $results[] = $book;
            }
        }
        
        return $results;
    }
    
    /**
     * Normalize Google Books API response
     */
    private function normalizeGoogleBook(array $item): ?array {
        if (!isset($item['volumeInfo'])) {
            return null;
        }
        
        $volumeInfo = $item['volumeInfo'];
        
        $book = [
            'title' => $volumeInfo['title'] ?? '',
            'authors' => $volumeInfo['authors'] ?? [],
            'description' => $volumeInfo['description'] ?? null,
            'page_count' => $volumeInfo['pageCount'] ?? null,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'cover_image_url' => null,
            'isbn' => null,
        ];
        
        // Extract ISBN
        if (isset($volumeInfo['industryIdentifiers'])) {
            foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                if ($identifier['type'] === 'ISBN_13' || $identifier['type'] === 'ISBN_10') {
                    $book['isbn'] = $identifier['identifier'];
                    break;
                }
            }
        }
        
        // Get cover image
        if (isset($volumeInfo['imageLinks'])) {
            $book['cover_image_url'] = $volumeInfo['imageLinks']['thumbnail'] 
                ?? $volumeInfo['imageLinks']['smallThumbnail'] 
                ?? null;
        }
        
        return $book;
    }
    
    /**
     * Search Open Library API
     */
    private function searchOpenLibrary(string $query): array {
        $url = self::OPEN_LIBRARY_API . '?q=' . urlencode($query) . '&limit=' . self::MAX_RESULTS;
        
        // Try cURL first (more reliable)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'OkayReads/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                error_log("Open Library API error: HTTP $httpCode - $error");
                return [];
            }
        } else {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'user_agent' => 'OkayReads/1.0',
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("Open Library API error: file_get_contents failed");
                return [];
            }
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Open Library API error: JSON decode failed - " . json_last_error_msg());
            return [];
        }
        
        if (!isset($data['docs']) || !is_array($data['docs']) || empty($data['docs'])) {
            return [];
        }
        
        $results = [];
        foreach ($data['docs'] as $doc) {
            $book = $this->normalizeOpenLibraryBook($doc);
            if ($book) {
                $results[] = $book;
            }
        }
        
        return $results;
    }
    
    /**
     * Normalize Open Library API response
     */
    private function normalizeOpenLibraryBook(array $doc): ?array {
        if (empty($doc['title'])) {
            return null;
        }
        
        $book = [
            'title' => $doc['title'],
            'authors' => $doc['author_name'] ?? [],
            'description' => null,
            'page_count' => isset($doc['number_of_pages_median']) ? (int)$doc['number_of_pages_median'] : null,
            'published_date' => isset($doc['first_publish_year']) ? (string)$doc['first_publish_year'] : null,
            'cover_image_url' => null,
            'isbn' => null,
        ];
        
        // Extract ISBN
        if (isset($doc['isbn']) && is_array($doc['isbn']) && !empty($doc['isbn'])) {
            $book['isbn'] = $doc['isbn'][0];
        }
        
        // Get cover image
        if (isset($doc['cover_i'])) {
            $book['cover_image_url'] = 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-M.jpg';
        }
        
        return $book;
    }
}

