<?php
/**
 * Simple test to verify upload directory setup
 */

echo "=== UPLOAD DIRECTORY TEST ===\n\n";

$uploadDir = __DIR__ . '/../uploads/food-items/';
echo "Upload directory: " . $uploadDir . "\n";
echo "Real path: " . realpath($uploadDir) . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

if (!is_dir($uploadDir)) {
    echo "\nCreating directory...\n";
    $result = mkdir($uploadDir, 0777, true);
    echo "Creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($result) {
        echo "Directory now exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
        echo "Directory now writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";
    }
}

// Test file creation
echo "\nTesting file creation...\n";
$testFile = $uploadDir . 'test.txt';
$testContent = 'Test content';

try {
    $bytes = file_put_contents($testFile, $testContent);
    if ($bytes !== false) {
        echo "File created successfully: " . $bytes . " bytes\n";
        echo "File exists: " . (file_exists($testFile) ? 'YES' : 'NO') . "\n";
        
        // Clean up
        unlink($testFile);
        echo "Test file cleaned up\n";
    } else {
        echo "File creation failed\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
