<?php
// Test script to verify PUT method handling
error_log("=== PUT METHOD TEST SCRIPT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Raw Input: " . file_get_contents("php://input"));

header('Content-Type: application/json');

// Test data
$testData = [
    'id' => '1',
    'name' => 'Test PUT Item',
    'price' => '15.99',
    'category_id' => '1'
];

echo json_encode([
    'status' => 'success',
    'message' => 'PUT method test',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'raw_input' => file_get_contents("php://input"),
    'test_data' => $testData
]);
?>
