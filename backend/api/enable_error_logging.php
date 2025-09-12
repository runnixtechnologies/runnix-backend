<?php
// Enable detailed error logging
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

// Set custom error handler to capture all errors
set_error_handler(function($severity, $message, $file, $line) {
    $error = [
        'timestamp' => date('Y-m-d H:i:s'),
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'type' => 'ERROR'
    ];
    
    error_log(json_encode($error) . "\n", 3, __DIR__ . '/../php-error.log');
    
    // Also log to default error log
    error_log("PHP Error: $message in $file on line $line");
    
    return false; // Let PHP handle the error normally
});

// Set exception handler
set_exception_handler(function($exception) {
    $error = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'type' => 'EXCEPTION'
    ];
    
    error_log(json_encode($error) . "\n", 3, __DIR__ . '/../php-error.log');
    
    // Also log to default error log
    error_log("PHP Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception caught and logged',
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
});

echo json_encode([
    'status' => 'success',
    'message' => 'Error logging enabled',
    'error_log_path' => __DIR__ . '/../php-error.log',
    'error_reporting' => error_reporting(),
    'log_errors' => ini_get('log_errors'),
    'display_errors' => ini_get('display_errors')
]);
