<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// Include necessary files
require_once '../config/Database.php';
require_once '../middleware/authMiddleware.php';
require_once '../model/PackageDelivery.php';

header('Content-Type: application/json');

use Model\PackageDelivery;
use function Middleware\authenticateRequest;

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $packageDeliveryModel = new PackageDelivery();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $requiredFields = ['receiver_name', 'receiver_phone', 'receiver_address', 'delivery_fee'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "{$field} is required."
                ]);
                exit;
            }
        }
        
        // Validate phone number format
        if (!preg_match('/^[0-9+\-\s()]+$/', $data['receiver_phone'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number format."
            ]);
            exit;
        }
        
        // Validate delivery fee
        if (!is_numeric($data['delivery_fee']) || $data['delivery_fee'] <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Delivery fee must be a positive number."
            ]);
            exit;
        }
        
        // Prepare package data
        $packageData = [
            'sender_id' => $user['user_id'],
            'receiver_name' => trim($data['receiver_name']),
            'receiver_phone' => trim($data['receiver_phone']),
            'receiver_address' => trim($data['receiver_address']),
            'receiver_latitude' => $data['receiver_latitude'] ?? null,
            'receiver_longitude' => $data['receiver_longitude'] ?? null,
            'package_description' => $data['package_description'] ?? null,
            'package_value' => $data['package_value'] ?? 0.00,
            'delivery_fee' => $data['delivery_fee'],
            'insurance_fee' => $data['insurance_fee'] ?? 0.00,
            'pickup_instructions' => $data['pickup_instructions'] ?? null,
            'delivery_instructions' => $data['delivery_instructions'] ?? null
        ];
        
        // Create package delivery
        $packageId = $packageDeliveryModel->createPackageDelivery($packageData);
        
        if ($packageId) {
            // Get package details for response
            $package = $packageDeliveryModel->getPackageDeliveryDetails($packageId);
            
            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Package delivery request created successfully',
                'data' => [
                    'package_id' => $packageId,
                    'package_number' => $package['package_number'],
                    'status' => $package['status'],
                    'delivery_fee' => $package['delivery_fee'],
                    'total_amount' => $package['delivery_fee'] + $package['insurance_fee']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Failed to create package delivery request."
            ]);
        }
        
    } catch (Exception $e) {
        $errorMessage = 'Send package error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while creating package delivery request."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed. Only POST requests are supported."
    ]);
}
?>
