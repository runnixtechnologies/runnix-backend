<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DiscountController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

authenticateRequest();
$controller = new DiscountController();

// ✅ Use GET method to get the store_id from the query string
$storeId = $_GET['store_id'] ?? null;

if (!$storeId) {
    echo json_encode([
        "status" => "error",
        "message" => "store_id is missing"
    ]);
    exit;
}

$response = $controller->getAllDiscountsByStore($storeId);
echo json_encode($response);
