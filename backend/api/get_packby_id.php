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

$id = $_GET['id'] ?? null;

if ($id) {
    $response = $controller->getPackById($id);
} else {
    http_response_code(400); // Bad Request
    $response = ['status' => 'error', 'message' => 'id parameter is required'];
}

echo json_encode($response);
