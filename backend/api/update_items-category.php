<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\ItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true) ?? [];

$user = authenticateRequest();
$controller = new ItemController(); // or FoodItemController if you're merging logic
$response = $controller->bulkUpdateCategory($data, $user);
echo json_encode($response);
