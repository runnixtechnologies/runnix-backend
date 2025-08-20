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
        'message' => 'Only merchants can update food sections.'
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

// Check for section ID in both 'id' and 'section_id' fields
if (!isset($data['id']) && !isset($data['section_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
    exit;
}

// Validate items array is required
if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Items array is required and must not be empty.'
    ]);
    exit;
}

// Validate each item in the array
foreach ($data['items'] as $index => $item) {
    if (!isset($item['name']) || empty($item['name'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => "Item at index {$index} must have a name."
        ]);
        exit;
    }
    
    if (!isset($item['price']) || !is_numeric($item['price']) || $item['price'] < 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => "Item '{$item['name']}' must have a valid price (non-negative number)."
        ]);
        exit;
    }
}

// Use section_id if available, otherwise use id
$id = isset($data['section_id']) ? $data['section_id'] : $data['id'];

$controller = new FoodItemController();
$response = $controller->updateFoodSection($id, $data, $user);

echo json_encode($response);
?>
