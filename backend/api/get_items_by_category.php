<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\ItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Get the logged-in user info
$user = authenticateRequest();

if (!isset($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing category_id parameter."]);
    exit;
}

$categoryId = intval($_GET['category_id']);

$controller = new ItemController();
$response = $controller->getItemsByCategoryInStore($user, $categoryId);
echo json_encode($response);
