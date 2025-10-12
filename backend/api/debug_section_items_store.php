<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only merchants can access section items']);
        exit;
    }

    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.']);
        exit;
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;

    echo json_encode([
        'status' => 'success',
        'message' => 'Debug info for get_all_section_items_in_store',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'get_data' => $_GET,
        'user_data' => [
            'user_id' => $user['user_id'] ?? 'not set',
            'role' => $user['role'] ?? 'not set',
            'store_id' => $user['store_id'] ?? 'not set'
        ],
        'parameters' => [
            'page' => $page,
            'limit' => $limit,
            'section_id' => $sectionId
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
