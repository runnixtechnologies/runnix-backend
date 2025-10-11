<?php
/**
 * Test error logging configuration
 */

echo "=== ERROR LOGGING TEST ===\n\n";

echo "Error log file: " . ini_get('error_log') . "\n";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "Error reporting: " . error_reporting() . "\n";

// Test writing to error log
error_log("TEST: Error log test message from test_error_logging.php");

echo "Test error message written to log\n";

// Test directory and file operations
$uploadDir = __DIR__ . '/../../uploads/food-items/';
echo "\nUpload directory: " . $uploadDir . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

// Test file creation
$testFile = $uploadDir . 'test_' . time() . '.txt';
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
        echo "Last error: " . json_encode(error_get_last()) . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
