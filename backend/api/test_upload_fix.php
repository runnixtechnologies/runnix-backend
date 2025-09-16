<?php
/**
 * Test upload fix with new path resolution
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== UPLOAD FIX TEST STARTED ===");

try {
    // Test the new path resolution
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
    
    error_log("Upload directory: " . $uploadDir);
    error_log("Document root: " . $_SERVER['DOCUMENT_ROOT']);
    error_log("Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO'));
    error_log("Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        error_log("Creating upload directory...");
        $createResult = mkdir($uploadDir, 0777, true);
        error_log("Directory creation result: " . ($createResult ? 'SUCCESS' : 'FAILED'));
        
        if (!$createResult) {
            error_log("Failed to create directory. Last error: " . json_encode(error_get_last()));
        }
    }
    
    // Test file upload if available
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        error_log("Photo file detected:");
        error_log("  - Name: " . $_FILES['photo']['name']);
        error_log("  - Size: " . $_FILES['photo']['size'] . " bytes");
        error_log("  - Type: " . $_FILES['photo']['type']);
        error_log("  - Temp name: " . $_FILES['photo']['tmp_name']);
        
        // Test upload with new path
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'test_fix_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        error_log("Attempting upload:");
        error_log("  - Source: " . $_FILES['photo']['tmp_name']);
        error_log("  - Destination: " . $uploadPath);
        error_log("  - Upload directory: " . $uploadDir);
        error_log("  - Real path: " . realpath($uploadDir));
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            error_log("Upload SUCCESS!");
            error_log("File exists: " . (file_exists($uploadPath) ? 'YES' : 'NO'));
            error_log("File size: " . filesize($uploadPath) . " bytes");
            
            // Clean up
            unlink($uploadPath);
            error_log("Test file cleaned up");
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Upload test successful with new path resolution',
                'details' => [
                    'upload_dir' => $uploadDir,
                    'real_path' => realpath($uploadDir),
                    'filename' => $filename,
                    'size' => $_FILES['photo']['size']
                ]
            ]);
        } else {
            error_log("Upload FAILED!");
            error_log("Last error: " . json_encode(error_get_last()));
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Upload failed with new path resolution',
                'error' => error_get_last(),
                'upload_dir' => $uploadDir,
                'real_path' => realpath($uploadDir)
            ]);
        }
    } else {
        error_log("No photo file uploaded");
        echo json_encode([
            'status' => 'info',
            'message' => 'No photo file uploaded - upload directory test completed',
            'upload_dir' => $uploadDir,
            'real_path' => realpath($uploadDir),
            'exists' => is_dir($uploadDir),
            'writable' => is_writable($uploadDir)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Exception in upload fix test: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred during test',
        'error' => $e->getMessage()
    ]);
}

error_log("=== UPLOAD FIX TEST COMPLETED ===");
?>
