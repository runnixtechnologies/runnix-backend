<?php
/**
 * Simple test for photo upload
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "=== SIMPLE PHOTO UPLOAD TEST ===\n\n";

// Test 1: Check if we can create a test image
echo "1. Creating test image...\n";
$testImagePath = __DIR__ . '/test_image.jpg';
$image = imagecreate(100, 100);
$bgColor = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 5, 20, 40, 'TEST', $textColor);
imagejpeg($image, $testImagePath);
imagedestroy($image);
echo "✓ Test image created: " . $testImagePath . "\n";

// Test 2: Check upload directory
echo "\n2. Checking upload directory...\n";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
echo "Upload directory: " . $uploadDir . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

if (!is_dir($uploadDir)) {
    echo "Creating directory...\n";
    mkdir($uploadDir, 0777, true);
    echo "Directory created: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
}

// Test 3: Test file operations
echo "\n3. Testing file operations...\n";
$testDestPath = $uploadDir . 'test_' . uniqid() . '.jpg';
echo "Test destination: " . $testDestPath . "\n";

if (copy($testImagePath, $testDestPath)) {
    echo "✓ File copy successful\n";
    echo "File size: " . filesize($testDestPath) . " bytes\n";
    unlink($testDestPath);
    echo "✓ Test file cleaned up\n";
} else {
    echo "✗ File copy failed\n";
}

// Test 4: Check PHP configuration
echo "\n4. PHP Configuration...\n";
echo "File uploads enabled: " . (ini_get('file_uploads') ? 'YES' : 'NO') . "\n";
echo "Max file size: " . ini_get('upload_max_filesize') . "\n";
echo "Max post size: " . ini_get('post_max_size') . "\n";
echo "Max execution time: " . ini_get('max_execution_time') . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";

// Test 5: Check $_FILES simulation
echo "\n5. Testing \$_FILES simulation...\n";
if (isset($_FILES['photo'])) {
    echo "✓ \$_FILES['photo'] exists\n";
    echo "File info: " . json_encode($_FILES['photo']) . "\n";
} else {
    echo "✗ \$_FILES['photo'] not found\n";
    echo "Available FILES: " . json_encode($_FILES) . "\n";
}

// Clean up test image
unlink($testImagePath);

echo "\n=== TEST COMPLETED ===\n";

echo json_encode([
    'status' => 'test_completed',
    'upload_directory' => $uploadDir,
    'directory_exists' => is_dir($uploadDir),
    'directory_writable' => is_writable($uploadDir),
    'files_data' => $_FILES,
    'php_config' => [
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ]
]);
?>
