<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type header once
header('Content-Type: application/json');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php'; // Your custom CORS config

use Controller\UserController;

// Get the incoming POST data
$data = json_decode(file_get_contents("php://input"), true);

// Check if data was correctly decoded
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
    exit;
}

// Instantiate UserController and handle the login
$userController = new UserController();
$response = $userController->login($data);

// Send the response
echo json_encode($response);
