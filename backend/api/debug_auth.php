<?php
// Debug script for authentication issues
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../middleware/authMiddleware.php';

use Middleware;

try {
    echo json_encode([
        'status' => 'debug',
        'message' => 'Authentication debug information',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'headers' => getallheaders(),
        'get_params' => $_GET,
        'auth_header' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'Not set',
        'server_vars' => [
            'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'Not set',
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage()
    ]);
}
