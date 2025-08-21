<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get item ID from URL parameter
    $itemId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
        exit;
    }

    // Try to authenticate user
    try {
        $user = authenticateRequest();
        
        // Debug information
        echo json_encode([
            'status' => 'success',
            'message' => 'Authentication successful',
            'debug_info' => [
                'user_id' => $user['user_id'] ?? 'not_set',
                'store_id' => $user['store_id'] ?? 'not_set',
                'role' => $user['role'] ?? 'not_set',
                'requested_item_id' => $itemId,
                'headers_received' => [
                    'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'present' : 'missing',
                    'content_type' => $_SERVER['HTTP_CONTENT_TYPE'] ?? 'not_set'
                ]
            ]
        ]);
        
    } catch (Exception $authError) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication failed',
            'debug_info' => [
                'error' => $authError->getMessage(),
                'headers_received' => [
                    'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'present' : 'missing',
                    'content_type' => $_SERVER['HTTP_CONTENT_TYPE'] ?? 'not_set'
                ]
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
