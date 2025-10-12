<?php
/**
 * Comprehensive upload debugging script
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== COMPREHENSIVE UPLOAD DEBUG ===\n\n";

// Test 1: Basic PHP settings
echo "1. PHP Upload Settings:\n";
echo "   file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   max_input_time: " . ini_get('max_input_time') . "\n\n";

// Test 2: Directory structure
echo "2. Directory Structure:\n";
$uploadDir = __DIR__ . '/../../uploads/food-items/';
echo "   Upload directory: " . $uploadDir . "\n";
echo "   Real path: " . realpath($uploadDir) . "\n";
echo "   Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "   Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadDir)) {
    echo "   Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
}

// Test 3: Check if we can create files
echo "\n3. File Creation Test:\n";
$testFile = $uploadDir . 'debug_test_' . time() . '.txt';
$testContent = 'Debug test content';

try {
    $bytes = file_put_contents($testFile, $testContent);
    if ($bytes !== false) {
        echo "   File creation: SUCCESS (" . $bytes . " bytes)\n";
        echo "   File exists: " . (file_exists($testFile) ? 'YES' : 'NO') . "\n";
        
        // Clean up
        unlink($testFile);
        echo "   Test file cleaned up\n";
    } else {
        echo "   File creation: FAILED\n";
        echo "   Last error: " . json_encode(error_get_last()) . "\n";
    }
} catch (Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

// Test 4: Check $_FILES if available
echo "\n4. File Upload Test:\n";
if (isset($_FILES['photo'])) {
    echo "   Photo file detected:\n";
    echo "   - Name: " . $_FILES['photo']['name'] . "\n";
    echo "   - Size: " . $_FILES['photo']['size'] . " bytes\n";
    echo "   - Type: " . $_FILES['photo']['type'] . "\n";
    echo "   - Error: " . $_FILES['photo']['error'] . "\n";
    echo "   - Temp name: " . $_FILES['photo']['tmp_name'] . "\n";
    
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        echo "   Upload error: NONE (OK)\n";
        
        // Test move_uploaded_file
        $filename = 'debug_' . uniqid() . '.jpg';
        $uploadPath = $uploadDir . $filename;
        
        echo "   Attempting to move file to: " . $uploadPath . "\n";
        $moveResult = move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);
        
        if ($moveResult) {
            echo "   File move: SUCCESS\n";
            echo "   File exists at destination: " . (file_exists($uploadPath) ? 'YES' : 'NO') . "\n";
            echo "   File size at destination: " . filesize($uploadPath) . " bytes\n";
            
            // Clean up test file
            unlink($uploadPath);
            echo "   Test file cleaned up\n";
        } else {
            echo "   File move: FAILED\n";
            echo "   Last error: " . json_encode(error_get_last()) . "\n";
        }
    } else {
        echo "   Upload error: " . $_FILES['photo']['error'] . "\n";
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        echo "   Error meaning: " . ($errorMessages[$_FILES['photo']['error']] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   No photo file uploaded (this is normal for direct script access)\n";
}

// Test 5: Check error logging
echo "\n5. Error Logging Test:\n";
echo "   Error log file: " . ini_get('error_log') . "\n";
echo "   Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";

// Test writing to error log
error_log("DEBUG: Test error log message from debug_upload_comprehensive.php");
echo "   Test error message written to log\n";

echo "\n=== DEBUG COMPLETE ===\n";
?>
