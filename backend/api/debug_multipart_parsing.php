<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
error_log("Content-Type: " . $contentType);

if (stripos($contentType, 'multipart/form-data') !== false) {
    error_log("Processing multipart/form-data PUT request");
    
    $rawInput = file_get_contents("php://input");
    error_log("Raw input length: " . strlen($rawInput));
    error_log("Raw input preview: " . substr($rawInput, 0, 500) . "...");
    
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
            
            // Parse form field
            if (preg_match('/name="([^"]+)"\s*\r?\n\r?\n(.*?)(?=\r?\n--|$)/s', $part, $matches)) {
                $fieldName = trim($matches[1]);
                $fieldValue = trim($matches[2]);
                $data[$fieldName] = $fieldValue;
                error_log("Found field: $fieldName = " . substr($fieldValue, 0, 100) . "...");
                
                // Check if it's JSON data
                if (in_array($fieldName, ['sides', 'packs', 'sections'])) {
                    $decoded = json_decode($fieldValue, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        error_log("Successfully decoded JSON for $fieldName: " . json_encode($decoded));
                        $data[$fieldName] = $decoded;
                    } else {
                        error_log("Failed to decode JSON for $fieldName: " . json_last_error_msg());
                        error_log("Raw value: " . $fieldValue);
                    }
                }
            }
            
            // Parse file field
            if (preg_match('/name="([^"]+)"; filename="([^"]*)"\s*\r?\nContent-Type:\s*([^\r\n]+)\s*\r?\n\r?\n(.*?)(?=\r?\n--|$)/s', $part, $matches)) {
                $fieldName = trim($matches[1]);
                $fileName = trim($matches[2]);
                $fileType = trim($matches[3]);
                $fileContent = $matches[4];
                
                error_log("Found file: $fieldName = $fileName ($fileType, " . strlen($fileContent) . " bytes)");
                
                $files[$fieldName] = [
                    'name' => $fileName,
                    'type' => $fileType,
                    'size' => strlen($fileContent)
                ];
            }
        }
        
        error_log("Final parsed data: " . json_encode($data));
        error_log("Files found: " . json_encode($files));
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Multipart parsing completed',
            'data' => $data,
            'files' => $files,
            'content_type' => $contentType,
            'raw_input_length' => strlen($rawInput)
        ]);
        
    } else {
        error_log("Could not detect boundary in Content-Type: " . $contentType);
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not detect boundary in Content-Type',
            'content_type' => $contentType
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Expected multipart/form-data content type',
        'content_type' => $contentType
    ]);
}


