<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log that the endpoint was hit
error_log("=== CREATE FOOD ITEM ENDPOINT HIT ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Method: " . $_SERVER['REQUEST_METHOD']);

// Log incoming request for debugging
error_log("=== API CREATE FOOD ITEM REQUEST ===");
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw POST: " . json_encode($_POST));
error_log("Raw FILES: " . json_encode($_FILES));
error_log("Raw Input: " . file_get_contents("php://input"));

// Handle content type (JSON or FormData)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    error_log("Parsed as JSON: " . json_encode($data));
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    error_log("Parsed as FormData: " . json_encode($data));
    
    // Parse JSON strings for sides, packs, and sections when using form-data
    if (isset($data['sides']) && is_string($data['sides'])) {
        error_log("Sides before JSON decode: " . $data['sides']);
        $data['sides'] = json_decode($data['sides'], true);
        error_log("Sides after JSON decode: " . json_encode($data['sides']));
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error for sides: " . json_last_error_msg());
        }
    }
    if (isset($data['packs']) && is_string($data['packs'])) {
        error_log("Packs before JSON decode: " . $data['packs']);
        $data['packs'] = json_decode($data['packs'], true);
        error_log("Packs after JSON decode: " . json_encode($data['packs']));
    }
    if (isset($data['sections']) && is_string($data['sections'])) {
        error_log("Sections before JSON decode: " . $data['sections']);
        $data['sections'] = json_decode($data['sections'], true);
        error_log("Sections after JSON decode: " . json_encode($data['sections']));
    }
    if (isset($data['section_items']) && is_string($data['section_items'])) {
        error_log("Section items before JSON decode: " . $data['section_items']);
        $data['section_items'] = json_decode($data['section_items'], true);
        error_log("Section items after JSON decode: " . json_encode($data['section_items']));
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error for section_items: " . json_last_error_msg());
        }
    }
    
    // Convert boolean strings to actual booleans for mobile app compatibility
    $data = convertBooleanStrings($data);
} else {
    $data = $_POST;
}

// Debug: Log the received data
error_log("=== CREATE FOOD ITEM DEBUG ===");
error_log("Content-Type: " . $contentType);
error_log("Raw input: " . file_get_contents("php://input"));
error_log("POST data: " . json_encode($_POST));
error_log("Parsed data: " . json_encode($data));

// Additional debug for sides data
if (isset($data['sides'])) {
    error_log("=== SIDES DATA DEBUG ===");
    error_log("Sides data: " . json_encode($data['sides']));
    error_log("Sides type: " . gettype($data['sides']));
    if (is_array($data['sides'])) {
        error_log("Sides keys: " . implode(', ', array_keys($data['sides'])));
        if (isset($data['sides']['items'])) {
            error_log("Sides items: " . json_encode($data['sides']['items']));
            error_log("Sides items type: " . gettype($data['sides']['items']));
            error_log("Sides items is_array: " . (is_array($data['sides']['items']) ? 'true' : 'false'));
            error_log("Sides items count: " . (is_array($data['sides']['items']) ? count($data['sides']['items']) : 'N/A'));
        } else {
            error_log("ERROR: Sides items key is missing!");
        }
    }
    error_log("=== END SIDES DATA DEBUG ===");
} else {
    error_log("ERROR: Sides data is not set!");
}

try {
    error_log("=== STARTING AUTHENTICATION ===");
    $user = authenticateRequest();
    error_log("=== AUTHENTICATION SUCCESS ===");
    error_log("User data: " . json_encode($user));
    
    error_log("=== STARTING FOOD ITEM CREATION ===");
    $controller = new FoodItemController();
    $response = $controller->create($data, $user);
    error_log("=== FOOD ITEM CREATION COMPLETED ===");
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
} catch (Exception $e) {
    error_log("=== ERROR IN CREATE FOOD ITEM ===");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}

/**
 * Convert boolean strings to actual booleans for mobile app compatibility
 * Only converts fields that are meant to be booleans, not numeric fields
 */
function convertBooleanStrings($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = convertBooleanStrings($value);
            } elseif (is_string($value)) {
                // Only convert fields that are meant to be booleans
                if (in_array($key, ['required'])) {
                    $lowerValue = strtolower(trim($value));
                    if ($lowerValue === 'true' || $lowerValue === '1') {
                        $data[$key] = true;
                    } elseif ($lowerValue === 'false' || $lowerValue === '0') {
                        $data[$key] = false;
                    }
                }
            } elseif (is_numeric($value)) {
                // Only convert numeric values for boolean fields, not for max_quantity
                if (in_array($key, ['required'])) {
                    if ($value === 1) {
                        $data[$key] = true;
                    } elseif ($value === 0) {
                        $data[$key] = false;
                    }
                }
            }
        }
    }
    return $data;
}
