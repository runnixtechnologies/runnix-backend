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

echo json_encode([
    'status' => 'success',
    'message' => 'Debug endpoint working',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'get_data' => $_GET,
    'post_data' => $_POST,
    'raw_input' => file_get_contents("php://input"),
    'headers' => getallheaders()
]);
