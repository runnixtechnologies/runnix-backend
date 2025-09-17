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

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log that the endpoint was hit
error_log("=== UPDATE BUSINESS PROFILE ENDPOINT HIT ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Method: " . $_SERVER['REQUEST_METHOD']);

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
    // Handle PUT requests with different content types
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    error_log("=== PARSING REQUEST DATA ===");
    error_log("Content-Type: " . $contentType);
    
    $data = [];
    
    if (stripos($contentType, 'application/json') !== false) {
        // Handle JSON data
        error_log("Processing JSON request");
        $rawInput = file_get_contents("php://input");
        error_log("Raw input: " . $rawInput);
        
        $data = json_decode($rawInput, true);
        error_log("Parsed data: " . json_encode($data));
        
        // Check if JSON is valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("=== ERROR: INVALID JSON ===");
            error_log("JSON decode error: " . json_last_error_msg());
            http_response_code(400);
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid JSON data: " . json_last_error_msg()
            ]);
            exit;
        }
    } elseif (stripos($contentType, 'multipart/form-data') !== false) {
        // Handle multipart/form-data (for file uploads)
        error_log("Processing multipart/form-data request");
        
        // For PUT with multipart/form-data, try a simpler approach
        // First, try to use $_POST and $_FILES if they're populated
        if (!empty($_POST) || !empty($_FILES)) {
            error_log("Using existing \$_POST and \$_FILES data");
            $data = $_POST;
            error_log("POST data: " . json_encode($data));
            error_log("FILES data: " . json_encode($_FILES));
        } else {
            error_log("No \$_POST or \$_FILES data, attempting manual parsing");
            
            // Manual multipart parsing as fallback
            $rawInput = file_get_contents("php://input");
            error_log("Raw PUT input for form-data: " . substr($rawInput, 0, 500) . "...");
            
            $data = [];
            $files = [];
            
            if (preg_match('/boundary=([^\s;]+)/', $contentType, $matches)) {
                $boundary = '--' . $matches[1];
                error_log("Detected boundary: " . $boundary);
                
                $parts = explode($boundary, $rawInput);
                error_log("Number of parts: " . count($parts));
                
                foreach ($parts as $i => $part) {
                    if (empty(trim($part)) || $part === '--') continue;
                    
                    error_log("Processing part $i: " . substr($part, 0, 100) . "...");
                    
                    // Split header and content
                    $headerEndPos = strpos($part, "\r\n\r\n");
                    if ($headerEndPos === false) {
                        $headerEndPos = strpos($part, "\n\n");
                    }
                    
                    if ($headerEndPos !== false) {
                        $header = substr($part, 0, $headerEndPos);
                        $content = substr($part, $headerEndPos + 4); // Skip \r\n\r\n or \n\n
                        
                        error_log("Header: " . $header);
                        error_log("Content length: " . strlen($content));
                        
                        // Check if this is a file field
                        if (preg_match('/name="([^"]+)"; filename="([^"]*)"/', $header, $fileMatches)) {
                            $fieldName = trim($fileMatches[1]);
                            $fileName = trim($fileMatches[2]);
                            
                            // Extract content type
                            $fileType = 'application/octet-stream'; // default
                            if (preg_match('/Content-Type:\s*([^\r\n]+)/', $header, $typeMatches)) {
                                $fileType = trim($typeMatches[1]);
                            }
                            
                            // Create temporary file for uploaded content
                            $tempFile = tempnam(sys_get_temp_dir(), 'put_upload_');
                            $writeResult = file_put_contents($tempFile, $content);
                            
                            if ($writeResult !== false) {
                                $files[$fieldName] = [
                                    'name' => $fileName,
                                    'type' => $fileType,
                                    'tmp_name' => $tempFile,
                                    'error' => UPLOAD_ERR_OK,
                                    'size' => strlen($content)
                                ];
                                
                                error_log("Found file: $fieldName = $fileName ($fileType, " . strlen($content) . " bytes)");
                            } else {
                                error_log("Failed to write temporary file for: $fieldName");
                            }
                        }
                        // Check if this is a regular form field
                        elseif (preg_match('/name="([^"]+)"/', $header, $fieldMatches)) {
                            $fieldName = trim($fieldMatches[1]);
                            $fieldValue = trim($content);
                            $data[$fieldName] = $fieldValue;
                            error_log("Found field: $fieldName = $fieldValue");
                        }
                    }
                }
                
                // Add files to $_FILES superglobal for compatibility
                if (!empty($files)) {
                    $_FILES = array_merge($_FILES, $files);
                    error_log("Added files to \$_FILES: " . json_encode(array_keys($files)));
                }
                
                error_log("Parsed form-data: " . json_encode($data));
            } else {
                error_log("Could not detect boundary in Content-Type: " . $contentType);
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
    
    // Check if data is provided
    if (empty($data)) {
        error_log("=== ERROR: NO DATA PROVIDED ===");
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "No data provided in request body"
        ]);
        exit;
    }
    
    // Authenticate user
    error_log("=== STARTING AUTHENTICATION ===");
    $user = authenticateRequest();
    if (!$user) {
        error_log("=== AUTHENTICATION FAILED ===");
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    error_log("=== AUTHENTICATION SUCCESS ===");
    error_log("User data: " . json_encode($user));
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        error_log("=== ERROR: NOT A MERCHANT ===");
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only merchants can update business profiles']);
        exit;
    }
    
    error_log("=== STARTING BUSINESS PROFILE UPDATE ===");
    error_log("Update data: " . json_encode($data));
    
    $controller = new StoreController();
    $response = $controller->updateStoreProfile($data, $user);
    
    error_log("=== BUSINESS PROFILE UPDATE COMPLETED ===");
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
    
    // Clean up any temporary files created during multipart parsing
    // Only clean up if the update was successful
    if (isset($response) && isset($response['status']) && $response['status'] === 'success') {
        if (isset($_FILES) && !empty($_FILES)) {
            foreach ($_FILES as $file) {
                if (isset($file['tmp_name']) && strpos($file['tmp_name'], 'put_upload_') !== false) {
                    if (file_exists($file['tmp_name'])) {
                        unlink($file['tmp_name']);
                        error_log("Cleaned up temporary file after successful update: " . $file['tmp_name']);
                    }
                }
            }
        }
    } else {
        error_log("Update failed, keeping temporary files for debugging");
    }
    
} catch (Exception $e) {
    error_log("=== ERROR IN UPDATE BUSINESS PROFILE ===");
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
?>
