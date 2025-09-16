<?php
/**
 * Debug script to test image upload functionality
 */

echo "=== IMAGE UPLOAD DEBUG ===\n\n";

// Test directory creation and permissions
$uploadDir = __DIR__ . '/../uploads/food-items/';
echo "Upload directory path: " . $uploadDir . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

// Check parent directory permissions
$parentDir = __DIR__ . '/../uploads/';
echo "Parent directory path: " . $parentDir . "\n";
echo "Parent directory exists: " . (is_dir($parentDir) ? 'YES' : 'NO') . "\n";
echo "Parent directory writable: " . (is_writable($parentDir) ? 'YES' : 'NO') . "\n";

// Try to create directory
echo "\nAttempting to create directory...\n";
if (!is_dir($uploadDir)) {
    $result = mkdir($uploadDir, 0777, true);
    echo "Directory creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    if (!$result) {
        echo "Error details:\n";
        echo "- Last error: " . error_get_last()['message'] . "\n";
        echo "- Directory permissions: " . substr(sprintf('%o', fileperms($parentDir)), -4) . "\n";
    }
} else {
    echo "Directory already exists\n";
}

// Check if we can write a test file
echo "\nTesting file write permissions...\n";
$testFile = $uploadDir . 'test_write.txt';
$testContent = 'This is a test file to check write permissions';

try {
    $writeResult = file_put_contents($testFile, $testContent);
    if ($writeResult !== false) {
        echo "File write test: SUCCESS (wrote " . $writeResult . " bytes)\n";
        // Clean up test file
        unlink($testFile);
        echo "Test file cleaned up\n";
    } else {
        echo "File write test: FAILED\n";
    }
} catch (Exception $e) {
    echo "File write test: EXCEPTION - " . $e->getMessage() . "\n";
}

// Check PHP upload settings
echo "\nPHP Upload Settings:\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

// Check if we can access $_FILES
echo "\nFILES superglobal accessible: " . (isset($_FILES) ? 'YES' : 'NO') . "\n";

// Simulate file upload test
echo "\nSimulating file upload test...\n";
if (isset($_FILES['photo'])) {
    echo "Photo file detected:\n";
    echo "- Name: " . $_FILES['photo']['name'] . "\n";
    echo "- Size: " . $_FILES['photo']['size'] . " bytes\n";
    echo "- Type: " . $_FILES['photo']['type'] . "\n";
    echo "- Error: " . $_FILES['photo']['error'] . "\n";
    echo "- Temp name: " . $_FILES['photo']['tmp_name'] . "\n";
    
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        echo "Upload error: NONE (OK)\n";
        
        // Test move_uploaded_file
        $filename = uniqid('test_', true) . '.jpg';
        $uploadPath = $uploadDir . $filename;
        
        echo "Attempting to move file to: " . $uploadPath . "\n";
        $moveResult = move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);
        
        if ($moveResult) {
            echo "File move: SUCCESS\n";
            echo "File exists at destination: " . (file_exists($uploadPath) ? 'YES' : 'NO') . "\n";
            echo "File size at destination: " . filesize($uploadPath) . " bytes\n";
            
            // Clean up test file
            unlink($uploadPath);
            echo "Test file cleaned up\n";
        } else {
            echo "File move: FAILED\n";
            echo "Error details: " . error_get_last()['message'] . "\n";
        }
    } else {
        echo "Upload error: " . $_FILES['photo']['error'] . "\n";
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        echo "Error meaning: " . ($errorMessages[$_FILES['photo']['error']] ?? 'Unknown error') . "\n";
    }
} else {
    echo "No photo file uploaded (this is normal for direct script access)\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
