<?php
/**
 * Debug upload endpoint with proper error logging
 */

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== DEBUG UPLOAD ENDPOINT STARTED ===");

try {
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Test upload directory
    $uploadDir = __DIR__ . '/../../uploads/food-items/';
    error_log("Upload directory: " . $uploadDir);
    error_log("Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO'));
    error_log("Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
    
    // Check $_FILES
    error_log("FILES superglobal: " . json_encode($_FILES));
    
    // Check $_POST
    error_log("POST superglobal: " . json_encode($_POST));
    
    // Test file upload if available
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        error_log("Photo file detected:");
        error_log("  - Name: " . $_FILES['photo']['name']);
        error_log("  - Size: " . $_FILES['photo']['size'] . " bytes");
        error_log("  - Type: " . $_FILES['photo']['type']);
        error_log("  - Temp name: " . $_FILES['photo']['tmp_name']);
        
        // Test upload
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'debug_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        error_log("Attempting upload to: " . $uploadPath);
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            error_log("Upload SUCCESS!");
            error_log("File exists: " . (file_exists($uploadPath) ? 'YES' : 'NO'));
            error_log("File size: " . filesize($uploadPath) . " bytes");
            
            // Clean up
            unlink($uploadPath);
            error_log("Test file cleaned up");
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Upload test successful',
                'details' => [
                    'filename' => $filename,
                    'size' => $_FILES['photo']['size'],
                    'type' => $_FILES['photo']['type']
                ]
            ]);
        } else {
            error_log("Upload FAILED!");
            error_log("Last error: " . json_encode(error_get_last()));
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Upload failed',
                'error' => error_get_last()
            ]);
        }
    } else {
        error_log("No photo file uploaded");
        echo json_encode([
            'status' => 'info',
            'message' => 'No photo file uploaded - this is normal for testing'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Exception in debug upload: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred',
        'error' => $e->getMessage()
    ]);
}

error_log("=== DEBUG UPLOAD ENDPOINT COMPLETED ===");
?>
