<?php
/**
 * Test endpoint to verify authentication is working
 * This helps debug authentication issues
 */

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log headers for debugging
error_log("Headers received: " . json_encode(getallheaders()));

try {
    $user = authenticateRequest();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Authentication successful',
        'user' => [
            'user_id' => $user['user_id'] ?? 'not_set',
            'role' => $user['role'] ?? 'not_set',
            'store_id' => $user['store_id'] ?? 'not_set'
        ],
        'headers_received' => array_keys(getallheaders())
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage(),
        'debug_info' => [
            'headers_available' => array_keys(getallheaders()),
            'authorization_header' => getallheaders()['Authorization'] ?? 'not_found',
            'server_vars' => [
                'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'not_found',
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'not_found'
            ]
        ]
    ]);
}
