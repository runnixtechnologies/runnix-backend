<?php
/**
 * Test file copy functionality for manually parsed files
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== FILE COPY TEST STARTED ===");

try {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
    
    error_log("Upload directory: " . $uploadDir);
    error_log("Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO'));
    error_log("Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
    
    // Test file upload if available
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        error_log("Photo file detected:");
        error_log("  - Name: " . $_FILES['photo']['name']);
        error_log("  - Size: " . $_FILES['photo']['size'] . " bytes");
        error_log("  - Type: " . $_FILES['photo']['type']);
        error_log("  - Temp name: " . $_FILES['photo']['tmp_name']);
        
        // Test both move_uploaded_file and copy
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        
        // Test 1: move_uploaded_file
        $filename1 = 'test_move_' . uniqid() . '.' . $ext;
        $uploadPath1 = $uploadDir . $filename1;
        
        error_log("Testing move_uploaded_file:");
        error_log("  - Source: " . $_FILES['photo']['tmp_name']);
        error_log("  - Destination: " . $uploadPath1);
        
        $moveResult = move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath1);
        error_log("move_uploaded_file result: " . ($moveResult ? 'SUCCESS' : 'FAILED'));
        
        if ($moveResult) {
            error_log("File moved successfully, size: " . filesize($uploadPath1) . " bytes");
            unlink($uploadPath1);
            error_log("Test file cleaned up");
        } else {
            error_log("move_uploaded_file failed, last error: " . json_encode(error_get_last()));
        }
        
        // Test 2: copy (for manually parsed files)
        $filename2 = 'test_copy_' . uniqid() . '.' . $ext;
        $uploadPath2 = $uploadDir . $filename2;
        
        error_log("Testing copy:");
        error_log("  - Source: " . $_FILES['photo']['tmp_name']);
        error_log("  - Destination: " . $uploadPath2);
        
        $copyResult = copy($_FILES['photo']['tmp_name'], $uploadPath2);
        error_log("copy result: " . ($copyResult ? 'SUCCESS' : 'FAILED'));
        
        if ($copyResult) {
            error_log("File copied successfully, size: " . filesize($uploadPath2) . " bytes");
            unlink($uploadPath2);
            error_log("Test file cleaned up");
        } else {
            error_log("copy failed, last error: " . json_encode(error_get_last()));
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'File copy test completed',
            'results' => [
                'move_uploaded_file' => $moveResult ? 'SUCCESS' : 'FAILED',
                'copy' => $copyResult ? 'SUCCESS' : 'FAILED'
            ]
        ]);
        
    } else {
        error_log("No photo file uploaded");
        echo json_encode([
            'status' => 'info',
            'message' => 'No photo file uploaded - upload directory test completed',
            'upload_dir' => $uploadDir,
            'exists' => is_dir($uploadDir),
            'writable' => is_writable($uploadDir)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Exception in file copy test: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred during test',
        'error' => $e->getMessage()
    ]);
}

error_log("=== FILE COPY TEST COMPLETED ===");
?>
