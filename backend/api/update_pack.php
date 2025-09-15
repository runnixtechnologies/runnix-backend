<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\PackController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Check if it's a PUT request
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed. Use PUT method."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user = authenticateRequest();
$controller = new PackController();

$response = $controller->update($data, $user);
echo json_encode($response);
