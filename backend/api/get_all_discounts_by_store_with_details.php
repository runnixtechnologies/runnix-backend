<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DiscountController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$user = authenticateRequest();
$controller = new DiscountController();

$response = $controller->getAllDiscountsByStoreWithDetails($user);
echo json_encode($response);
