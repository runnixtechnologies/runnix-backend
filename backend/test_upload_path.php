<?php
/**
 * Test upload path resolution
 */

echo "=== UPLOAD PATH TEST ===\n\n";

echo "Current working directory: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Script file: " . __FILE__ . "\n\n";

// Test the path we're using
$uploadDir = __DIR__ . '/../../uploads/food-items/';
echo "Upload directory path: " . $uploadDir . "\n";
echo "Real path: " . realpath($uploadDir) . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

// List contents of uploads directory
$uploadsDir = __DIR__ . '/../../uploads/';
echo "\nUploads directory: " . $uploadsDir . "\n";
echo "Uploads directory exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadsDir)) {
    echo "Contents of uploads directory:\n";
    $contents = scandir($uploadsDir);
    foreach ($contents as $item) {
        if ($item != '.' && $item != '..') {
            $itemPath = $uploadsDir . $item;
            echo "- " . $item . " (" . (is_dir($itemPath) ? 'DIR' : 'FILE') . ")\n";
        }
    }
}

// Test file creation in food-items directory
echo "\nTesting file creation in food-items directory...\n";
$testFile = $uploadDir . 'test_' . time() . '.txt';
$testContent = 'Test content for upload verification';

try {
    $bytes = file_put_contents($testFile, $testContent);
    if ($bytes !== false) {
        echo "File created successfully: " . $bytes . " bytes\n";
        echo "File exists: " . (file_exists($testFile) ? 'YES' : 'NO') . "\n";
        echo "File path: " . $testFile . "\n";
        
        // Clean up
        unlink($testFile);
        echo "Test file cleaned up\n";
    } else {
        echo "File creation failed\n";
        echo "Last error: " . json_encode(error_get_last()) . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
