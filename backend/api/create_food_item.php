<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Handle content type (JSON or FormData)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    
    // Parse JSON strings for sides, packs, and sections when using form-data
    if (isset($data['sides']) && is_string($data['sides'])) {
        $data['sides'] = json_decode($data['sides'], true);
    }
    if (isset($data['packs']) && is_string($data['packs'])) {
        $data['packs'] = json_decode($data['packs'], true);
    }
    if (isset($data['sections']) && is_string($data['sections'])) {
        $data['sections'] = json_decode($data['sections'], true);
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

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->create($data,$user);
echo json_encode($response);

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
