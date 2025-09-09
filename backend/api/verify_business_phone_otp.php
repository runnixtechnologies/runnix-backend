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

use Controller\OtpController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log that the endpoint was hit
error_log("=== VERIFY BUSINESS PHONE OTP ENDPOINT HIT ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Method: " . $_SERVER['REQUEST_METHOD']);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed. Use POST method."
    ]);
    exit;
}

try {
    // Get JSON data from raw input
    error_log("=== PARSING REQUEST DATA ===");
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
    
    // Validate required fields
    if (!isset($data['phone']) || empty($data['phone'])) {
        error_log("=== ERROR: PHONE NUMBER MISSING ===");
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Phone number is required"
        ]);
        exit;
    }
    
    if (!isset($data['otp']) || empty($data['otp'])) {
        error_log("=== ERROR: OTP MISSING ===");
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "OTP is required"
        ]);
        exit;
    }
    
    // Validate phone number format
    $phone = $data['phone'];
    if (!preg_match('/^0?\d{10}$/', $phone)) {
        error_log("=== ERROR: INVALID PHONE FORMAT ===");
        error_log("Provided phone: " . $phone);
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Invalid phone number format. Use 10 or 11 digits."
        ]);
        exit;
    }
    
    // Validate OTP format (6 digits)
    $otp = $data['otp'];
    if (!preg_match('/^\d{6}$/', $otp)) {
        error_log("=== ERROR: INVALID OTP FORMAT ===");
        error_log("Provided OTP: " . $otp);
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Invalid OTP format. Use 6 digits."
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
        echo json_encode(['status' => 'error', 'message' => 'Only merchants can verify business phone numbers']);
        exit;
    }
    
    // Format phone number to international format
    $formattedPhone = '234' . ltrim($phone, '0');
    error_log("Formatted phone: " . $formattedPhone);
    error_log("OTP: " . $otp);
    
    error_log("=== VERIFYING OTP FOR BUSINESS PHONE UPDATE ===");
    
    // Verify OTP for business phone update
    $otpController = new OtpController();
    $response = $otpController->verifyOtp($formattedPhone, $otp, 'business_phone_update');
    
    error_log("=== OTP VERIFICATION COMPLETED ===");
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("=== ERROR IN VERIFY BUSINESS PHONE OTP ===");
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
