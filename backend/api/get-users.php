<?php
header('Content-Type: application/json');
require_once '../middleware/authMiddleware.php';
require_once '../controller/UserController.php';

$auth = authenticateRequest(); // Authenticates and returns payload
authorizeRoles(['admin'], $auth['role']); // Only allow 'admin'

$controller = new \Controller\UserController();
$users = $controller->getAllUsers(); // Hypothetical function
echo json_encode(["status" => "success", "data" => $users]);
