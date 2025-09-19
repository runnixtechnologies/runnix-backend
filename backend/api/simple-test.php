<?php
// Ultra-simple test endpoint
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Test basic PHP functionality
$test = [
    "status" => "success",
    "message" => "Simple test endpoint working",
    "timestamp" => date('Y-m-d H:i:s'),
    "php_version" => PHP_VERSION,
    "server_method" => $_SERVER['REQUEST_METHOD'],
    "request_uri" => $_SERVER['REQUEST_URI']
];

// Test file writing
$logFile = __DIR__ . '/php-error.log';
$testMessage = "[" . date('Y-m-d H:i:s') . "] Simple test - PHP is working\n";
file_put_contents($logFile, $testMessage, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo json_encode($test);
?>
