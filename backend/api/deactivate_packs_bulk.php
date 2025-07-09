<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\PackController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true) ?? [];

authenticateRequest();
$controller = new PackController();
$response = $controller->deactivatePacksBulk($data);
echo json_encode($response);
