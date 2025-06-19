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

$data = $_GET; // expecting 'id' parameter

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->getByItemId($data['id'], $user);
echo json_encode($response);
