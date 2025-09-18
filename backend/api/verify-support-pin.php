<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Debugging: Log incoming request data
    error_log("Support pin verification request: " . print_r($data, true));

    if (isset($data->support_pin)) {
        try {
            $userIdentifier = $data->user_identifier ?? null; // Optional email or phone for additional verification
            
            $response = $supportController->verifySupportPin($data->support_pin, $userIdentifier);
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Support pin verification error: ' . $e->getMessage());
            echo json_encode([
                "status" => "error", 
                "message" => "An error occurred while verifying support pin."
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Support pin is required."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed. Only POST requests are supported."
    ]);
}
?>
