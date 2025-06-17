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

$controller = new ItemController();
$response = $controller->getAllItems($user); // pass the user object
echo json_encode($response);
