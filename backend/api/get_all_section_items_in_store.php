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

// Handle both GET and POST methods for consistency
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = $_GET;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use GET or POST method']);
    exit;
}

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
$page = isset($data['page']) ? (int)$data['page'] : 1;
$limit = isset($data['limit']) ? (int)$data['limit'] : 10;

// Get section_id parameter (optional)
$sectionId = isset($data['section_id']) ? (int)$data['section_id'] : null;

$controller = new FoodItemController();
$response = $controller->getAllSectionItemsInStore($user, $page, $limit, $sectionId);
echo json_encode($response);
