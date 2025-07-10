<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';
use Controller\FoodItemController;
use function Middleware\authenticateRequest;

$user = authenticateRequest();
$id = $_GET['id'] ?? null;

$controller = new FoodItemController();
$response = $controller->activateFoodSide($id, $user);
echo json_encode($response);
