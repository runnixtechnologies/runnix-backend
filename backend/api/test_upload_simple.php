<?php
/**
 * Simple upload test endpoint
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "=== SIMPLE UPLOAD TEST ===\n\n";

// Test 1: Check PHP settings
echo "PHP Upload Settings:\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n\n";

// Test 2: Check request method and content type
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n\n";

// Test 3: Check $_FILES
echo "FILES superglobal:\n";
if (isset($_FILES) && !empty($_FILES)) {
    foreach ($_FILES as $key => $file) {
        echo "File '$key':\n";
        echo "  - Name: " . $file['name'] . "\n";
        echo "  - Size: " . $file['size'] . " bytes\n";
        echo "  - Type: " . $file['type'] . "\n";
        echo "  - Error: " . $file['error'] . "\n";
        echo "  - Temp name: " . $file['tmp_name'] . "\n";
    }
} else {
    echo "No files in \$_FILES\n";
}

// Test 4: Check $_POST
echo "\nPOST data:\n";
if (isset($_POST) && !empty($_POST)) {
    foreach ($_POST as $key => $value) {
        echo "POST '$key': " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
} else {
    echo "No POST data\n";
}

// Test 5: Check raw input
echo "\nRaw input (first 200 chars):\n";
$rawInput = file_get_contents("php://input");
echo substr($rawInput, 0, 200) . "\n";

// Test 6: Directory test
echo "\nDirectory test:\n";
$uploadDir = __DIR__ . '/../../uploads/food-items/';
echo "Upload directory: " . $uploadDir . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

// Test 7: File upload test
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    echo "\nFile upload test:\n";
    
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = 'test_' . uniqid() . '.' . $ext;
    $uploadPath = $uploadDir . $filename;
    
    echo "Attempting to upload: " . $filename . "\n";
    echo "Source: " . $_FILES['photo']['tmp_name'] . "\n";
    echo "Destination: " . $uploadPath . "\n";
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        echo "Upload SUCCESS!\n";
        echo "File exists: " . (file_exists($uploadPath) ? 'YES' : 'NO') . "\n";
        echo "File size: " . filesize($uploadPath) . " bytes\n";
        
        // Clean up
        unlink($uploadPath);
        echo "Test file cleaned up\n";
    } else {
        echo "Upload FAILED!\n";
        echo "Last error: " . json_encode(error_get_last()) . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
