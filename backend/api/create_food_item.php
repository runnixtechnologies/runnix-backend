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
error_log("Received data: " . json_encode($data));
error_log("Content-Type: " . $contentType);
error_log("POST data: " . json_encode($_POST));
error_log("Raw input: " . file_get_contents("php://input"));

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->create($data,$user);
echo json_encode($response);

/**
 * Convert boolean strings to actual booleans for mobile app compatibility
 */
function convertBooleanStrings($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = convertBooleanStrings($value);
            } elseif (is_string($value)) {
                // Convert common boolean string representations
                $lowerValue = strtolower(trim($value));
                if ($lowerValue === 'true' || $lowerValue === '1') {
                    $data[$key] = true;
                } elseif ($lowerValue === 'false' || $lowerValue === '0') {
                    $data[$key] = false;
                }
            } elseif (is_numeric($value)) {
                // Convert numeric boolean representations
                if ($value === 1) {
                    $data[$key] = true;
                } elseif ($value === 0) {
                    $data[$key] = false;
                }
            }
        }
    }
    return $data;
}
