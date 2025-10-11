<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php'; 

use Controller\ItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true) ?? [];
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
} else {
    // fallback for form-encoded or unknown
    $data = $_POST;
}
$user = authenticateRequest();
$controller = new ItemController();
$response = $controller->addItemsBulk($data, $user);
echo json_encode($response);
