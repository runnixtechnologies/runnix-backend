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

$data = $_GET; // Or $_POST depending on request method

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->getAllFoodItemsByStoreId($data, $user);
echo json_encode($response);
