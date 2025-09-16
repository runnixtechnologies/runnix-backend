<?php
/**
 * Test error logging to backend folder
 */

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

echo "=== ERROR LOGGING TEST ===\n\n";

echo "Error log file: " . ini_get('error_log') . "\n";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n\n";

// Test different types of errors
echo "Testing error logging...\n";

// Test 1: Simple error log message
error_log("TEST 1: Simple error log message - " . date('Y-m-d H:i:s'));

// Test 2: Warning
trigger_error("TEST 2: This is a test warning", E_USER_WARNING);

// Test 3: Notice
trigger_error("TEST 3: This is a test notice", E_USER_NOTICE);

// Test 4: Exception
try {
    throw new Exception("TEST 4: This is a test exception");
} catch (Exception $e) {
    error_log("TEST 4: Exception caught - " . $e->getMessage());
}

// Test 5: File operation error
$nonExistentFile = __DIR__ . '/non_existent_file.txt';
file_get_contents($nonExistentFile);

echo "Error messages written to log\n";

// Check if log file was created and show contents
$logFile = __DIR__ . '/php-error.log';
echo "\nChecking log file: $logFile\n";
echo "File exists: " . (file_exists($logFile) ? 'YES' : 'NO') . "\n";

if (file_exists($logFile)) {
    echo "File size: " . filesize($logFile) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n";
    
    echo "\nLog file contents:\n";
    echo "==================\n";
    $contents = file_get_contents($logFile);
    echo $contents;
    echo "==================\n";
} else {
    echo "Log file was not created\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
