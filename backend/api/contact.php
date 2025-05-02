<?php
header("Access-Control-Allow-Origin: *"); // Allow all origins (or replace * with specific domains)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
/*   http_response_code(200);  // Respond with OK status for preflight request
    exit;
}*/

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
require_once '../model/Contact.php';
require_once '../controller/ContactController.php';

header('Content-Type: application/json');

use Controller\ContactController;

$contactController = new ContactController();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Debugging: Log incoming request data
    error_log("Incoming data: " . print_r($data, true));

    if (isset($data->fullname) && isset($data->email) && isset($data->phone) && isset($data->interest_complaints)
    && isset($data->message)) {
        try {
            //$status = isset($data->status) ? (int)$data->status : 1;
            
        $response = $contactController->handleFormSubmission($data->fullname, $data->email,
         $data->phone, $data->interest_complaints, $data->message);
            echo $response;
        } catch (Exception $e) {
            error_log('Error: ' . $e->getMessage());
            echo json_encode(["message" => "An error occurred while processing the request."]);
        }
    } else {
        echo json_encode(["message" => "Invalid input. All fields (name, email, phone,  message) are required."]);
    }
}


?>



