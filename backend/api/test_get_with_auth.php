<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'GET method with auth working',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'get_data' => $_GET,
        'user_data' => [
            'user_id' => $user['user_id'] ?? 'not set',
            'role' => $user['role'] ?? 'not set',
            'store_id' => $user['store_id'] ?? 'not set'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage()
    ]);
}
