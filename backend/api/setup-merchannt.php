<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

// Combine POST data and file data if multipart/form-data
$data = $_POST;

$userController = new UserController();
$response = $userController->setupMerchant($data);

echo json_encode($response);
