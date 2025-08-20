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

// Debug: Log the raw input
$rawInput = file_get_contents("php://input");
error_log("Raw input received: " . $rawInput);

$data = json_decode($rawInput, true);

// Debug: Log the decoded data
error_log("Decoded data: " . print_r($data, true));

// Debug: Check if JSON decode failed
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid JSON format: ' . json_last_error_msg(),
        'raw_input' => $rawInput
    ]);
    exit;
}

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->createFoodSide($data, $user);
echo json_encode($response);
