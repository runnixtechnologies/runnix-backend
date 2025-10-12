<?php
/**
 * Debug photo upload issue
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "=== DEBUG PHOTO UPLOAD ===\n\n";

echo "1. Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "2. Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
echo "3. POST data: " . json_encode($_POST) . "\n";
echo "4. FILES data: " . json_encode($_FILES) . "\n";
echo "5. REQUEST data: " . json_encode($_REQUEST) . "\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    echo "=== PUT REQUEST DEBUG ===\n";
    
    // Get raw input
    $rawInput = file_get_contents('php://input');
    echo "Raw input length: " . strlen($rawInput) . "\n";
    echo "Raw input preview: " . substr($rawInput, 0, 500) . "...\n\n";
    
    // Check if it's multipart
    if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        echo "=== MULTIPART FORM DATA DETECTED ===\n";
        
        // Parse boundary
        preg_match('/boundary=(.+)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1] ?? null;
        echo "Boundary: " . $boundary . "\n";
        
        if ($boundary) {
            // Parse multipart data manually
            $parts = explode('--' . $boundary, $rawInput);
            echo "Number of parts: " . count($parts) . "\n";
            
            foreach ($parts as $i => $part) {
                if (trim($part) === '' || trim($part) === '--') continue;
                
                echo "\n--- Part $i ---\n";
                echo "Part preview: " . substr($part, 0, 200) . "...\n";
                
                // Check if this part contains a file
                if (strpos($part, 'filename=') !== false) {
                    echo "*** FILE PART DETECTED ***\n";
                    
                    // Extract filename
                    preg_match('/filename="([^"]+)"/', $part, $filenameMatches);
                    $filename = $filenameMatches[1] ?? 'unknown';
                    echo "Filename: " . $filename . "\n";
                    
                    // Extract content type
                    preg_match('/Content-Type: ([^\r\n]+)/', $part, $contentTypeMatches);
                    $contentType = $contentTypeMatches[1] ?? 'unknown';
                    echo "Content-Type: " . $contentType . "\n";
                    
                    // Extract file content
                    $fileContentStart = strpos($part, "\r\n\r\n");
                    if ($fileContentStart !== false) {
                        $fileContent = substr($part, $fileContentStart + 4);
                        echo "File content size: " . strlen($fileContent) . " bytes\n";
                        
                        // Save to temporary file for testing
                        $tempFile = tempnam(sys_get_temp_dir(), 'debug_photo_');
                        file_put_contents($tempFile, $fileContent);
                        echo "Saved to temp file: " . $tempFile . "\n";
                        
                        // Check if it's a valid image
                        $imageInfo = getimagesize($tempFile);
                        if ($imageInfo !== false) {
                            echo "✓ Valid image detected: " . $imageInfo['mime'] . " (" . $imageInfo[0] . "x" . $imageInfo[1] . ")\n";
                        } else {
                            echo "✗ Invalid image file\n";
                        }
                        
                        unlink($tempFile);
                    }
                }
            }
        }
    }
}

echo "\n=== END DEBUG ===\n";

echo json_encode([
    'status' => 'debug',
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
    'post_data' => $_POST,
    'files_data' => $_FILES,
    'request_data' => $_REQUEST
]);
?>
