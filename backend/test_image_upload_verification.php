<?php
/**
 * Test image upload verification
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "=== IMAGE UPLOAD VERIFICATION ===\n\n";

// Test 1: Check if we can access the uploaded images from the logs
echo "1. Checking Recent Uploads from Logs:\n";
$recentImages = [
    'item_68c97c79b47db4.65033899.jpg' => 'PASTOR ADENIJI.jpg',
    'item_68c973d8a8da34.10740062.jpg' => 'Previous upload'
];

foreach ($recentImages as $filename => $description) {
    $url = 'https://api.runnix.africa/uploads/food-items/' . $filename;
    echo "Testing: " . $description . " (" . $filename . ")\n";
    echo "URL: " . $url . "\n";
    
    // Check if file exists on server
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
    $filePath = $uploadDir . $filename;
    
    if (file_exists($filePath)) {
        echo "✓ File exists on server: " . $filePath . "\n";
        echo "  Size: " . filesize($filePath) . " bytes\n";
        echo "  Modified: " . date('Y-m-d H:i:s', filemtime($filePath)) . "\n";
    } else {
        echo "✗ File NOT found on server: " . $filePath . "\n";
    }
    
    // Test URL accessibility
    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "✓ URL accessible: " . $url . "\n";
    } else {
        echo "✗ URL NOT accessible: " . $url . "\n";
        if ($headers) {
            echo "  Response: " . $headers[0] . "\n";
        }
    }
    echo "\n";
}

// Test 2: Check upload directory structure
echo "2. Upload Directory Analysis:\n";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
echo "Upload Directory: " . $uploadDir . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "Files in directory: " . (count($files) - 2) . "\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            echo "- " . $file . " (" . filesize($filePath) . " bytes, " . date('Y-m-d H:i:s', filemtime($filePath)) . ")\n";
        }
    }
}
echo "\n";

// Test 3: Test URL structure
echo "3. URL Structure Test:\n";
$baseUrl = 'https://api.runnix.africa/uploads/food-items/';
echo "Base URL: " . $baseUrl . "\n";

// Test 4: Check server configuration
echo "4. Server Configuration:\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";

echo "\n=== VERIFICATION COMPLETED ===\n";

echo json_encode([
    'status' => 'verification_completed',
    'upload_directory' => $uploadDir,
    'directory_exists' => is_dir($uploadDir),
    'directory_writable' => is_writable($uploadDir),
    'files_count' => is_dir($uploadDir) ? count(scandir($uploadDir)) - 2 : 0,
    'base_url' => $baseUrl,
    'recent_images' => $recentImages
]);
?>

