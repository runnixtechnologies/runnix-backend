<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DiscountController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['store_id']) || empty($_GET['store_id']) || !is_numeric($_GET['store_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid store ID is required']);
    exit;
}

$storeId = (int)$_GET['store_id'];
$user = authenticateRequest();
$controller = new DiscountController();

$response = $controller->getAllDiscountsByStoreWithDetails($storeId);
echo json_encode($response);
