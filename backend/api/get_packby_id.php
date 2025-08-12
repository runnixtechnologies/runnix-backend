<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\PackController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid pack ID is required']);
    exit;
}

$user = authenticateRequest();
$controller = new PackController();

$id = (int)$_GET['id'];
$response = $controller->getPackById($id, $user);

echo json_encode($response);
