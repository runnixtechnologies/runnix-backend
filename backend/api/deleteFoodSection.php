
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$user = authenticateRequest();

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
    exit;
}

$id = $data['id'];

$controller = new FoodItemController();
$response = $controller->deleteFoodSection($id, $user);

echo json_encode($response);
?>