<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\PackController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Debug: Log request method and GET parameters
error_log("get_packby_id API: Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("get_packby_id API: GET parameters: " . json_encode($_GET));

// Validate input
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    error_log("get_packby_id API: Invalid ID parameter: " . ($_GET['id'] ?? 'not set'));
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid pack ID is required']);
    exit;
}

$packId = (int)$_GET['id'];
error_log("get_packby_id API: Processing pack ID: $packId");

$user = authenticateRequest();
error_log("get_packby_id API: User authenticated: " . json_encode($user));

$controller = new PackController();
$response = $controller->getPackById($packId, $user);

error_log("get_packby_id API: Response: " . json_encode($response));
echo json_encode($response);
