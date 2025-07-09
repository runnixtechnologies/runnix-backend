<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\ItemController;
use function Middleware\authenticateRequest;

header ('Content-Type: application/json');

$user = authenticateRequest();

$data = [];


if ($_SERVER['CONTENT_TYPE'] ?? '' && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
    $data = array_change_key_case($_POST, CASE_LOWER); // Normalize keys
} else {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true) ?? [];
}

error_log("Final parsed data: " . print_r($data, true));

$controller = new ItemController();
$response = $controller->updateItem($data, $user);

echo json_encode($response);
