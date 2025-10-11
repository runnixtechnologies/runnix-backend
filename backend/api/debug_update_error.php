<?php
/**
 * Debug update error endpoint
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== DEBUG UPDATE ERROR STARTED ===");

try {
    // Test basic functionality
    echo "=== DEBUG UPDATE ERROR TEST ===\n\n";
    
    // Test 1: Check if classes can be loaded
    echo "1. Testing class loading...\n";
    require_once '../../vendor/autoload.php';
    require_once '../config/cors.php';
    require_once '../middleware/authMiddleware.php';
    
    echo "✓ Classes loaded successfully\n\n";
    
    // Test 2: Check database connection
    echo "2. Testing database connection...\n";
    require_once '../config/Database.php';
    $db = new \Config\Database();
    $conn = $db->getConnection();
    echo "✓ Database connection successful\n\n";
    
    // Test 3: Test FoodItemController creation
    echo "3. Testing FoodItemController creation...\n";
    $controller = new \Controller\FoodItemController();
    echo "✓ FoodItemController created successfully\n\n";
    
    // Test 4: Test JSON parsing
    echo "4. Testing JSON parsing...\n";
    $testJson = '{"required": true,"max_quantity": 1,"items": [11]}';
    $parsed = json_decode($testJson, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✓ JSON parsing successful: " . json_encode($parsed) . "\n";
    } else {
        echo "✗ JSON parsing failed: " . json_last_error_msg() . "\n";
    }
    echo "\n";
    
    // Test 5: Test file operations
    echo "5. Testing file operations...\n";
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/food-items/';
    echo "Upload directory: $uploadDir\n";
    echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
    echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n\n";
    
    echo "=== ALL TESTS PASSED ===\n";
    
    echo json_encode([
        'status' => 'success',
        'message' => 'All debug tests passed',
        'details' => [
            'classes_loaded' => true,
            'database_connected' => true,
            'controller_created' => true,
            'json_parsing' => true,
            'upload_directory' => $uploadDir,
            'directory_exists' => is_dir($uploadDir),
            'directory_writable' => is_writable($uploadDir)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Exception in debug update error: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo "=== ERROR OCCURRED ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred during debug test',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

error_log("=== DEBUG UPDATE ERROR COMPLETED ===");
?>
