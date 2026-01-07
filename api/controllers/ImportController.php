<?php
/**
 * Import Controller
 */

require_once __DIR__ . '/../services/ImportService.php';

class ImportController {
    private static function getUserId(): int {
        $user = AuthController::getCurrentUser();
        if (!$user) {
            Response::error('Not authenticated', 401);
            exit;
        }
        return (int)$user['id'];
    }
    
    /**
     * Import Goodreads CSV
     */
    public static function goodreads(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error');
            return;
        }
        
        $tmpFile = $_FILES['file']['tmp_name'];
        $userId = self::getUserId();
        
        try {
            $importService = new ImportService();
            $results = $importService->importGoodreadsCsv($tmpFile, $userId);
            
            Response::success($results, 'Import completed');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        } finally {
            // Clean up temp file
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }
    
    /**
     * Import ISBN list
     */
    public static function isbnList(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error');
            return;
        }
        
        $tmpFile = $_FILES['file']['tmp_name'];
        $userId = self::getUserId();
        
        try {
            $importService = new ImportService();
            $results = $importService->importIsbnList($tmpFile, $userId);
            
            Response::success($results, 'Import completed');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        } finally {
            // Clean up temp file
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }
}

