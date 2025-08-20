<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DiscountController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true) ?? [];

// Validate ID
if (!isset($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid discount ID is required']);
    exit;
}

$user = authenticateRequest(); // Auth middleware should return user/store info

$controller = new DiscountController();
$response = $controller->deleteDiscount($data['id'], $user);
echo json_encode($response);
