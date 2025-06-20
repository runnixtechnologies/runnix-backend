<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\PackController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$user = authenticateRequest();
$controller = new PackController();

$storeId = $_GET['store_id'] ?? null;

if ($storeId) {
    $response = $controller->getAll($storeId);
} else {
    http_response_code(400); // Bad Request
    $response = ['status' => 'error', 'message' => 'store_id parameter is required'];
}

echo json_encode($response);
