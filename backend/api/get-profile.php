<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\UserController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Enforce GET only
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
	http_response_code(405);
	header('Allow: GET');
	echo json_encode([
		'status' => 'error',
		'message' => 'Method Not Allowed. Use GET.'
	]);
	exit;
}

$user = authenticateRequest();

$controller = new UserController();
$response = $controller->getProfile($user);

echo json_encode($response);
