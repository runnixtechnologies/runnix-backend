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
if (!isset($_GET['pack_id']) || empty($_GET['pack_id']) || !is_numeric($_GET['pack_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid pack ID is required']);
    exit;
}

$packId = (int)$_GET['pack_id'];
$user = authenticateRequest();
$controller = new DiscountController();

$response = $controller->getDiscountsByPackId($packId);
echo json_encode($response);
