<?php
// Simple test to verify error logging is working

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

header('Content-Type: application/json');

// Test logging
error_log("=== LOGGING TEST ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("This is a test log entry");

echo json_encode([
    'status' => 'success',
    'message' => 'Logging test completed. Check php-error.log for test entries.',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
