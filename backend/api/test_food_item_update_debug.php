<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Check if it's a PUT request
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed. Use PUT method."
    ]);
    exit;
}

try {
    $user = authenticateRequest();
    error_log("User authenticated: " . json_encode($user));
} catch (Exception $e) {
    error_log("Authentication failed: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

// Parse the request data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
error_log("Content-Type: " . $contentType);

if (stripos($contentType, 'application/json') !== false) {
    $rawInput = file_get_contents("php://input");
    error_log("Raw JSON input: " . $rawInput);
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
        exit;
    }
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    error_log("Form data: " . json_encode($data));
    error_log("Files: " . json_encode($_FILES));
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported content type']);
    exit;
}

error_log("Final parsed data: " . json_encode($data));

// Validate required fields
if (!isset($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid food item ID is required']);
    exit;
}

// Test the update with detailed logging
try {
    $controller = new FoodItemController();
    
    // Log what we're about to send to the controller
    error_log("=== TESTING FOOD ITEM UPDATE ===");
    error_log("Food Item ID: " . $data['id']);
    error_log("Data being sent to controller: " . json_encode($data));
    error_log("User: " . json_encode($user));
    
    // Check if sides, packs, sections are present
    if (isset($data['sides'])) {
        error_log("Sides data present: " . json_encode($data['sides']));
    } else {
        error_log("No sides data provided");
    }
    
    if (isset($data['packs'])) {
        error_log("Packs data present: " . json_encode($data['packs']));
    } else {
        error_log("No packs data provided");
    }
    
    if (isset($data['sections'])) {
        error_log("Sections data present: " . json_encode($data['sections']));
    } else {
        error_log("No sections data provided");
    }
    
    if (isset($_FILES['photo'])) {
        error_log("Photo file present: " . json_encode($_FILES['photo']));
    } else {
        error_log("No photo file provided");
    }
    
    $response = $controller->update($data, $user);
    error_log("Controller response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("=== TEST UPDATE ERROR ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("Error Trace: " . $e->getTraceAsString());
    error_log("Data: " . json_encode($data));
    error_log("User: " . json_encode($user));
    error_log("=== END ERROR LOG ===");
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Test update failed: ' . $e->getMessage()]);
}


