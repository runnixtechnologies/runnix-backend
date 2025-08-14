
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
        'message' => 'Only merchants can delete food sections.'
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

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
    exit;
}

$id = $data['id'];

$controller = new FoodItemController();
$response = $controller->deleteFoodSection($id, $user);

echo json_encode($response);
?>