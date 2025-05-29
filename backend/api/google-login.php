<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';      // Your custom CORS config

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$userController = new UserController();
$response = $userController->googlePrefill($data);

echo json_encode($response);
