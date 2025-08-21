<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
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

$controller = new FoodItemController();
$response = $controller->getSectionItemById($itemId, $user);

echo json_encode($response);
?>
