<?php
/**
 * Export Controller
 */

class ExportController {
    private static function getUserId(): int {
        $user = AuthController::getCurrentUser();
        if (!$user) {
            Response::error('Not authenticated', 401);
            exit;
        }
        return (int)$user['id'];
    }
    
    /**
     * Export data as CSV
     */
    public static function csv(): void {
        $userId = self::getUserId();
        
        $userBookModel = new UserBook();
        $bookModel = new Book();
        
        $userBooks = $userBookModel->getUserBooks($userId);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="okayreads_export_' . date('Y-m-d') . '.csv"');
        header('Access-Control-Allow-Origin: *');
        
        // Output BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, [
            'Title',
            'Author(s)',
            'ISBN',
            'Status',
            'Date Started',
            'Date Finished',
            'Rating',
            'Review'
        ]);
        
        // Write data rows
        foreach ($userBooks as $userBook) {
            $bookId = $userBook['book_id'];
            $authors = $bookModel->getAuthors($bookId);
            $authorNames = array_column($authors, 'name');
            
            fputcsv($output, [
                $userBook['title'] ?? '',
                implode(', ', $authorNames),
                $userBook['isbn'] ?? '',
                $userBook['status'] ?? '',
                $userBook['date_started'] ?? '',
                $userBook['date_finished'] ?? '',
                $userBook['rating'] ?? '',
                $userBook['review'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
}

