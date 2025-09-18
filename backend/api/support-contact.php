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
require_once '../model/SupportContact.php';
require_once '../controller/SupportContactController.php';

header('Content-Type: application/json');

use Controller\SupportContactController;

$supportContactController = new SupportContactController();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Debugging: Log incoming request data
    error_log("Support contact form data: " . print_r($data, true));

    // Check if this is a support pin verification request
    if (isset($data->action) && $data->action === 'verify_pin') {
        if (isset($data->support_pin)) {
            try {
                $userIdentifier = $data->user_identifier ?? null;
                $response = $supportContactController->verifySupportPin($data->support_pin, $userIdentifier);
                echo $response;
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
                "message" => "Support pin is required for verification."
            ]);
        }
    } 
    // Check if this is a full support contact form submission
    elseif (isset($data->fullname) && isset($data->email) && isset($data->phone) && 
            isset($data->interest_complaints) && isset($data->message) && isset($data->support_pin)) {
        try {
            $userIdentifier = $data->user_identifier ?? null;
            $response = $supportContactController->handleSupportFormSubmission(
                $data->fullname,
                $data->email,
                $data->phone,
                $data->interest_complaints,
                $data->message,
                $data->support_pin,
                $userIdentifier
            );
            echo $response;
        } catch (Exception $e) {
            error_log('Support contact form error: ' . $e->getMessage());
            echo json_encode([
                "status" => "error", 
                "message" => "An error occurred while processing the support request."
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Invalid input. All fields (name, email, phone, message, support_pin) are required."
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
