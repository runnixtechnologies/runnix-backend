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
$user = authenticateRequest();

if (!isset($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing Category Id."]);
    exit;
}

$categoryId = intval($_GET['category_id']);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

$controller = new FoodItemController();
$response = $controller->getItemsByCategoryInStore($user, $categoryId, $page, $limit);
echo json_encode($response);
