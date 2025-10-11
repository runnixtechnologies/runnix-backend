<?php
// Test script to verify PUT method handling
error_log("=== PUT METHOD TEST SCRIPT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Raw Input: " . file_get_contents("php://input"));
error_log("POST data: " . json_encode($_POST));
error_log("REQUEST data: " . json_encode($_REQUEST));

header('Content-Type: application/json');

// Test data
$testData = [
    'id' => '1',
    'name' => 'Test PUT Item',
    'price' => '15.99',
    'category_id' => '1'
];

// Simulate the same parsing logic as the main endpoint
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'multipart/form-data') !== false) {
        if (!empty($_REQUEST)) {
            $data = $_REQUEST;
        } elseif (!empty($_POST)) {
            $data = $_POST;
        }
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'PUT method test',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'raw_input' => file_get_contents("php://input"),
    'parsed_data' => $data,
    'post_data' => $_POST,
    'request_data' => $_REQUEST,
    'test_data' => $testData
]);
?>
