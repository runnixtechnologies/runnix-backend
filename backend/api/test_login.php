<?php
// Test login endpoint to get a valid token
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../controller/UserController.php';

use Controller\UserController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid JSON data'
            ]);
            exit();
        }
        
        $userController = new UserController();
        $response = $userController->login($input);
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Login failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'info',
        'message' => 'Send POST request with login credentials',
        'example' => [
            'identifier' => 'your_email_or_phone',
            'password' => 'your_password'
        ],
        'note' => 'Use the returned token in Authorization header: Bearer <token>'
    ]);
}
