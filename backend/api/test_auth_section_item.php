<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Get the logged-in user info
$user = authenticateRequest(); // returns user details (user_id, role, store_id, etc.)

// Get item ID from URL parameter
$itemId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
    exit;
}

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
?>
