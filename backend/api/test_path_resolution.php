<?php
/**
 * Test path resolution on server
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== PATH RESOLUTION TEST ===");

echo "=== PATH RESOLUTION TEST ===\n\n";

echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Current Working Directory: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n\n";

// Test different path approaches
$paths = [
    'Relative from controller' => __DIR__ . '/../../uploads/food-items/',
    'Document root approach' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/',
    'Absolute from controller' => dirname(dirname(__DIR__)) . '/uploads/food-items/'
];

foreach ($paths as $name => $path) {
    echo "$name: $path\n";
    echo "  Exists: " . (is_dir($path) ? 'YES' : 'NO') . "\n";
    echo "  Writable: " . (is_writable($path) ? 'YES' : 'NO') . "\n";
    echo "  Real path: " . realpath($path) . "\n";
    
    error_log("$name: $path - Exists: " . (is_dir($path) ? 'YES' : 'NO') . " - Writable: " . (is_writable($path) ? 'YES' : 'NO'));
    
    // Test file creation
    if (is_dir($path) && is_writable($path)) {
        $testFile = $path . 'test_' . time() . '.txt';
        $testContent = 'Test content';
        
        try {
            $bytes = file_put_contents($testFile, $testContent);
            if ($bytes !== false) {
                echo "  File creation: SUCCESS ($bytes bytes)\n";
                unlink($testFile);
                echo "  Test file cleaned up\n";
            } else {
                echo "  File creation: FAILED\n";
            }
        } catch (Exception $e) {
            echo "  File creation: EXCEPTION - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

// Check uploads directory structure
$uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
echo "Uploads directory: $uploadsDir\n";
echo "Exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadsDir)) {
    echo "Contents:\n";
    $contents = scandir($uploadsDir);
    foreach ($contents as $item) {
        if ($item != '.' && $item != '..') {
            $itemPath = $uploadsDir . $item;
            echo "  - $item (" . (is_dir($itemPath) ? 'DIR' : 'FILE') . ")\n";
        }
    }
}

echo "\n=== TEST COMPLETE ===\n";
error_log("=== PATH RESOLUTION TEST COMPLETE ===");
?>
