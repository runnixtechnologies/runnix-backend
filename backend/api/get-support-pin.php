<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// Include necessary files
require_once '../config/Database.php';
require_once '../controller/SupportController.php';

header('Content-Type: application/json');

use Controller\SupportController;

$supportController = new SupportController();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get support pin by user ID from query parameters
    if (isset($_GET['user_id'])) {
        try {
            $response = $supportController->getSupportPin($_GET['user_id']);
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Get support pin error: ' . $e->getMessage());
            echo json_encode([
                "status" => "error", 
                "message" => "An error occurred while retrieving support pin."
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "User ID is required."
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get support pin by user ID from POST data
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->user_id)) {
        try {
            $response = $supportController->getSupportPin($data->user_id);
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Get support pin error: ' . $e->getMessage());
            echo json_encode([
                "status" => "error", 
                "message" => "An error occurred while retrieving support pin."
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "User ID is required."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed. Only GET and POST requests are supported."
    ]);
}
?>
