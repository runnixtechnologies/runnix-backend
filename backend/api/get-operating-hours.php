<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Enforce POST only
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	echo json_encode([
		'status' => 'error',
		'message' => 'Method Not Allowed. Use POST.'
	]);
	exit;
}

// Authenticate user
$user = authenticateRequest();

// Only merchants can access
if (!isset($user['role']) || $user['role'] !== 'merchant') {
	http_response_code(403);
	echo json_encode([
		'status' => 'error',
		'message' => 'Only merchants can access operating hours.'
	]);
	exit;
}

// Delegate to controller (controller handles extracting store_id from JWT or DB fallback)
$controller = new StoreController();
$response = $controller->getOperatingHours($user);

echo json_encode($response);
