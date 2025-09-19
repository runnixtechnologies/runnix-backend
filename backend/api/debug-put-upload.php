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
    
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only merchants can access this endpoint']);
        exit;
    }
    
    $debug = [];
    
    // Handle PUT requests with different content types
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $debug['content_type'] = $contentType;
    
    $data = [];
    $files = [];
    
    if (stripos($contentType, 'multipart/form-data') !== false) {
        $debug['processing'] = 'multipart/form-data';
        
        // Check if $_POST and $_FILES are populated
        if (!empty($_POST) || !empty($_FILES)) {
            $debug['using_superglobals'] = true;
            $data = $_POST;
            $files = $_FILES;
        } else {
            $debug['using_superglobals'] = false;
            $debug['parsing_manually'] = true;
            
            // Manual multipart parsing
            $rawInput = file_get_contents("php://input");
            $debug['raw_input_length'] = strlen($rawInput);
            $debug['raw_input_preview'] = substr($rawInput, 0, 200) . '...';
            
            if (preg_match('/boundary=([^\s;]+)/', $contentType, $matches)) {
                $boundary = '--' . $matches[1];
                $debug['boundary'] = $boundary;
                
                $parts = explode($boundary, $rawInput);
                $debug['parts_count'] = count($parts);
                
                foreach ($parts as $i => $part) {
                    if (empty(trim($part)) || $part === '--') continue;
                    
                    // Split header and content
                    $headerEndPos = strpos($part, "\r\n\r\n");
                    if ($headerEndPos === false) {
                        $headerEndPos = strpos($part, "\n\n");
                    }
                    
                    if ($headerEndPos !== false) {
                        $header = substr($part, 0, $headerEndPos);
                        $content = substr($part, $headerEndPos + 4);
                        
                        // Check if this is a file field
                        if (preg_match('/name="([^"]+)"; filename="([^"]*)"/', $header, $fileMatches)) {
                            $fieldName = trim($fileMatches[1]);
                            $fileName = trim($fileMatches[2]);
                            
                            // Extract content type
                            $fileType = 'application/octet-stream';
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
                                
                                $debug['parsed_files'][$fieldName] = [
                                    'name' => $fileName,
                                    'type' => $fileType,
                                    'size' => strlen($content),
                                    'temp_file' => $tempFile
                                ];
                            }
                        }
                        // Check if this is a regular form field
                        elseif (preg_match('/name="([^"]+)"/', $header, $fieldMatches)) {
                            $fieldName = trim($fieldMatches[1]);
                            $fieldValue = trim($content);
                            $data[$fieldName] = $fieldValue;
                            $debug['parsed_fields'][$fieldName] = $fieldValue;
                        }
                    }
                }
                
                // Add files to $_FILES superglobal for compatibility
                if (!empty($files)) {
                    $_FILES = array_merge($_FILES, $files);
                }
            }
        }
    } else {
        $debug['processing'] = 'other_content_type';
        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput, true);
        $debug['json_decode_success'] = (json_last_error() === JSON_ERROR_NONE);
    }
    
    $debug['final_data'] = $data;
    $debug['final_files'] = array_keys($files);
    $debug['_FILES_keys'] = array_keys($_FILES);
    
    // Check if biz_logo file was uploaded
    if (isset($_FILES['biz_logo'])) {
        $debug['biz_logo_file'] = [
            'name' => $_FILES['biz_logo']['name'],
            'type' => $_FILES['biz_logo']['type'],
            'size' => $_FILES['biz_logo']['size'],
            'error' => $_FILES['biz_logo']['error'],
            'tmp_name' => $_FILES['biz_logo']['tmp_name']
        ];
    } else {
        $debug['biz_logo_file'] = 'NOT_FOUND';
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Debug completed',
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    error_log("Debug PUT upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage()
    ]);
}
?>
