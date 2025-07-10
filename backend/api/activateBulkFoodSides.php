<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true); // Expecting ['ids' => [1,2,3,...]]

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->activateBulkFoodSides($data['ids'], $user);
echo json_encode($response);
