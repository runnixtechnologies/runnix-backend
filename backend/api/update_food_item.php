<?php

// Log that the script is starting
error_log("UPDATE FOOD ITEM SCRIPT STARTED - " . date('Y-m-d H:i:s'));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log the request method and content type
error_log("=== UPDATE FOOD ITEM DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);

// Log all superglobals for debugging
error_log("POST data: " . json_encode($_POST));
error_log("GET data: " . json_encode($_GET));
error_log("REQUEST data: " . json_encode($_REQUEST));
error_log("Raw Input: " . file_get_contents("php://input"));

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
error_log("Processing request with Content-Type: " . $contentType);

// Handle PUT requests properly
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    error_log("=== PROCESSING PUT REQUEST ===");
    error_log("Content-Type: " . $contentType);
    error_log("Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
    
    if (stripos($contentType, 'application/json') !== false) {
        error_log("PUT request with application/json");
        $rawInput = file_get_contents("php://input");
        error_log("Raw PUT input: " . $rawInput);
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            error_log("Raw input that failed to decode: " . $rawInput);
            $data = [];
        } else {
            error_log("Parsed JSON Data: " . json_encode($data));
        }
    } elseif (stripos($contentType, 'multipart/form-data') !== false) {
        error_log("PUT request with multipart/form-data");
        
        // Check if data is available in $_REQUEST (works for some PHP configurations)
        if (!empty($_REQUEST)) {
            $data = $_REQUEST;
            error_log("PUT Form Data from \$_REQUEST: " . json_encode($data));
        } elseif (!empty($_POST)) {
            $data = $_POST;
            error_log("PUT Form Data from \$_POST: " . json_encode($data));
        } else {
            // Parse raw input manually for multipart/form-data
            $rawInput = file_get_contents("php://input");
            error_log("Raw PUT input for form-data: " . $rawInput);
            
            // Simple boundary detection and parsing
            $boundary = null;
            if (preg_match('/boundary=([^\s;]+)/', $contentType, $matches)) {
                $boundary = '--' . $matches[1];
                error_log("Detected boundary: " . $boundary);
                
                $parts = explode($boundary, $rawInput);
                $data = [];
                
                foreach ($parts as $part) {
                    if (preg_match('/name="([^"]+)"\s*\r?\n\r?\n(.*?)(?=\r?\n--|$)/s', $part, $matches)) {
                        $data[trim($matches[1])] = trim($matches[2]);
                    }
                }
                error_log("Parsed form-data: " . json_encode($data));
            } else {
                error_log("Could not detect boundary, using empty data");
                $data = [];
            }
        }
    } else {
        error_log("PUT request with unknown content type, trying JSON fallback");
        // Try to parse as JSON for PUT requests
        $rawInput = file_get_contents("php://input");
        error_log("Raw PUT input (fallback): " . $rawInput);
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error (fallback): " . json_last_error_msg());
            $data = [];
        } else {
            error_log("Parsed JSON Data (fallback): " . json_encode($data));
        }
    }
    
    error_log("=== END PUT PROCESSING ===");
} else {
    // Handle POST requests as before
    error_log("Processing non-PUT request");
    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        error_log("JSON Data received: " . json_encode($data));
    } elseif (stripos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;  // File will be in $_FILES
        error_log("Form Data received: " . json_encode($data));
    } else {
        $data = $_POST;
        error_log("Default POST Data received: " . json_encode($data));
    }
}

error_log("Final processed data: " . json_encode($data));
error_log("Data type: " . gettype($data));
error_log("Data count: " . (is_array($data) ? count($data) : 'not array'));
error_log("Data keys: " . (is_array($data) ? implode(', ', array_keys($data)) : 'not array'));

// Check if data is empty
if (empty($data)) {
    error_log("ERROR: Data is empty after processing!");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No data received in PUT request']);
    exit;
}

try {
    error_log("Attempting authentication...");
    $user = authenticateRequest();
    error_log("User authenticated successfully: " . json_encode($user));
} catch (Exception $e) {
    error_log("Authentication failed: " . $e->getMessage());
    error_log("Authentication error trace: " . $e->getTraceAsString());
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

// Validate ID with detailed logging
error_log("Validating food item ID...");
error_log("ID field exists: " . (isset($data['id']) ? 'yes' : 'no'));
error_log("ID value: " . ($data['id'] ?? 'not set'));
error_log("ID type: " . gettype($data['id'] ?? null));

if (!isset($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
    error_log("ID validation failed - ID: " . ($data['id'] ?? 'not set'));
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid food item ID is required']);
    exit;
}

error_log("ID validation passed: " . $data['id']);

try {
    error_log("Creating FoodItemController...");
    $controller = new FoodItemController();
    error_log("Controller created successfully");
    
    error_log("Calling controller->update() with:");
    error_log("  Data: " . json_encode($data));
    error_log("  User: " . json_encode($user));
    
    $response = $controller->update($data, $user);
    error_log("Update response: " . json_encode($response));
    
    echo json_encode($response);
} catch (Exception $e) {
    // Enhanced error logging for debugging
    error_log("=== UPDATE FOOD ITEM ENDPOINT ERROR ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("Error Trace: " . $e->getTraceAsString());
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log("Final processed data: " . json_encode($data));
    error_log("User data: " . json_encode($user ?? 'not set'));
    error_log("=== END ENDPOINT ERROR LOG ===");
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error during update']);
}
