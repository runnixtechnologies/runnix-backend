<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\ItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Get the logged-in user info
$user = authenticateRequest(); // returns user details (user_id, role, etc.)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$controller = new ItemController();
$response = $controller->getAllItems($user, $page, $limit);

echo json_encode($response);
