<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/Database.php';
require_once '../controller/FoodItemController.php';
require_once '../middleware/authMiddleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Step 1: Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    echo json_encode([
        'step' => '1',
        'status' => 'success',
        'message' => 'JSON parsed successfully',
        'data' => $data
    ]);
    exit;

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
        exit;
    }

    // Step 2: Validate required fields
    if (!isset($data['section_id']) || empty($data['section_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        exit;
    }

    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item name is required']);
        exit;
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Valid price is required (non-negative number)']);
        exit;
    }

    // Step 3: Authenticate user
    $user = authenticateRequest();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }

    // Step 4: Create controller instance
    $controller = new FoodItemController();
    
    // Step 5: Call method
    $response = $controller->createSectionItem($data, $user);

    // Step 6: Set appropriate HTTP status code
    $statusCode = isset($response['status']) && $response['status'] === 'success' ? 201 : 400;
    http_response_code($statusCode);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
