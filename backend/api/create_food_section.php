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

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user = authenticateRequest();

// Check if user is a merchant
if ($user['role'] !== 'merchant') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Only merchants can create food sections.'
    ]);
    exit;
}

// Extract store_id from authenticated user
if (!isset($user['store_id'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'
    ]);
    exit;
}

// Add store_id to data
$data['store_id'] = $user['store_id'];

// Basic Validation
if (empty($data['section_name'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Section name is required.'
    ]);
    exit;
}

// Validate is_required and max_qty dependency
if (isset($data['is_required']) && $data['is_required'] == 1) {
    if (!isset($data['max_qty']) || empty($data['max_qty'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'max_qty is required when is_required is set to 1.'
        ]);
        exit;
    }
}

$controller = new FoodItemController();
$response = $controller->createFoodSection($data, $user);

echo json_encode($response);
?>
