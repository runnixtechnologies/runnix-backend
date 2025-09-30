<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate
$user = authenticateRequest();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use GET method.']);
    exit;
}

// Read and validate query params
$storeTypeId = isset($_GET['store_type_id']) ? (int)$_GET['store_type_id'] : 0;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'popular'; // popular | newest | closest
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;

if ($storeTypeId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'store_type_id is required']);
    exit;
}

// Allowed sorts only
$allowedSorts = ['popular', 'newest', 'closest'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'popular';
}

// Delegate to controller (this already supports filtering by store_type_id)
$controller = new StoreController();
$response = $controller->getStoresForCustomer($user, $storeTypeId, $search, $sort, $page, $limit);

echo json_encode($response);
?>


