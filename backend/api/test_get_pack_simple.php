<?php
// Simple test endpoint for pack GET request

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Test basic functionality
echo json_encode([
    'status' => 'success',
    'message' => 'Test endpoint is working',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
