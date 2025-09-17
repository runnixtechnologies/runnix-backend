<?php
/**
 * Test upload directory and file creation
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "=== UPLOAD DIRECTORY CHECK ===\n\n";

// Test 1: Check document root and paths
echo "1. Server Information:\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n\n";

// Test 2: Check upload directory
echo "2. Upload Directory Check:\n";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
echo "Upload Directory: " . $uploadDir . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Real path: " . realpath($uploadDir) . "\n\n";

// Test 3: List files in upload directory
echo "3. Files in Upload Directory:\n";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "Number of files: " . count($files) . "\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            echo "- " . $file . " (Size: " . filesize($filePath) . " bytes, Modified: " . date('Y-m-d H:i:s', filemtime($filePath)) . ")\n";
        }
    }
} else {
    echo "Directory does not exist!\n";
}
echo "\n";

// Test 4: Create a test file
echo "4. Creating Test File:\n";
$testFileName = 'test_' . uniqid() . '.txt';
$testFilePath = $uploadDir . $testFileName;
$testContent = "Test file created at " . date('Y-m-d H:i:s');

if (file_put_contents($testFilePath, $testContent)) {
    echo "✓ Test file created successfully: " . $testFileName . "\n";
    echo "File size: " . filesize($testFilePath) . " bytes\n";
    echo "File URL: https://api.runnix.africa/uploads/food-items/" . $testFileName . "\n";
    
    // Clean up test file
    unlink($testFilePath);
    echo "✓ Test file cleaned up\n";
} else {
    echo "✗ Failed to create test file\n";
}
echo "\n";

// Test 5: Check URL accessibility
echo "5. URL Accessibility Test:\n";
$testUrl = "https://api.runnix.africa/uploads/food-items/";
echo "Base URL: " . $testUrl . "\n";

// Test 6: Check recent uploads from logs
echo "6. Recent Upload Analysis:\n";
echo "From the logs, recent uploads:\n";
echo "- item_68c97c79b47db4.65033899.jpg (PASTOR ADENIJI.jpg)\n";
echo "- item_68c973d8a8da34.10740062.jpg (previous upload)\n";
echo "Expected URLs:\n";
echo "- https://api.runnix.africa/uploads/food-items/item_68c97c79b47db4.65033899.jpg\n";
echo "- https://api.runnix.africa/uploads/food-items/item_68c973d8a8da34.10740062.jpg\n\n";

echo "=== CHECK COMPLETED ===\n";

echo json_encode([
    'status' => 'check_completed',
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'upload_directory' => $uploadDir,
    'directory_exists' => is_dir($uploadDir),
    'directory_writable' => is_writable($uploadDir),
    'real_path' => realpath($uploadDir),
    'files_count' => is_dir($uploadDir) ? count(scandir($uploadDir)) - 2 : 0,
    'base_url' => 'https://api.runnix.africa/uploads/food-items/'
]);
?>

