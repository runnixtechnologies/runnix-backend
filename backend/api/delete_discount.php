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

$user = authenticateRequest(); // Auth middleware should return user/store info
$storeId = $user['store_id'] ?? 0;

$controller = new DiscountController();
$response = $controller->deleteDiscount($data['id'] ?? 0, $storeId);
echo json_encode($response);
