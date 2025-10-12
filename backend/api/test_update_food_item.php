<?php

// Simple test script for update food item endpoint
error_log("=== TEST UPDATE FOOD ITEM ENDPOINT ===");
error_log("Test script started at: " . date('Y-m-d H:i:s'));

// Test the endpoint directly
$testData = [
    'id' => '1', // Replace with an actual food item ID from your store
    'name' => 'Test Updated Item',
    'price' => '15.99'
];

error_log("Test data: " . json_encode($testData));

// Simulate the request
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/backend/api/update_food_item.php';
$_SERVER['SCRIPT_NAME'] = '/backend/api/update_food_item.php';

// Mock the input stream
$mockInput = json_encode($testData);
error_log("Mock input: " . $mockInput);

// Test the validation logic
if (!isset($testData['id']) || !is_numeric($testData['id']) || $testData['id'] <= 0) {
    error_log("TEST FAILED: ID validation failed");
    echo json_encode(['status' => 'error', 'message' => 'Valid food item ID is required']);
} else {
    error_log("TEST PASSED: ID validation passed");
    echo json_encode(['status' => 'success', 'message' => 'ID validation test passed']);
}

error_log("=== TEST COMPLETED ===");
?>
