<?php

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';


// Initialize the UserController
use Controller\UserController;
$userController = new UserController();

// Check if POST data exists
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the input data
    $data = json_decode(file_get_contents("php://input"), true);

    // Make sure all required fields are set
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || 
        empty($data['phone']) || empty($data['password']) || empty($data['confirm_password']) || 
        empty($data['role'])) {
        
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    // Call the signup method from UserController
    $response = $userController->signup($data);

    // Output the response
    echo json_encode($response);
    exit;
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}