<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';

use Controller\StoreController;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Check authentication
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Get query parameters
$storeTypeId = $_GET['store_type_id'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'popular';
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);

// Validate sort parameter
$allowedSorts = ['popular', 'newest', 'closest'];
if (!in_array($sort, $allowedSorts)) {
    $sort = 'popular';
}

// Validate pagination
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 20;

// Get user location for distance calculation
$userLocation = null;
if ($sort === 'closest') {
    $userModel = new \Model\User();
    $userLocation = $userModel->getUserLocation($user['user_id']);
}

// Call controller
$controller = new StoreController();
$response = $controller->getStoresForCustomer($storeTypeId, $search, $userLocation, $sort, $page, $limit);

echo json_encode($response);
