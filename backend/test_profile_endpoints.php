<?php
/**
 * Test script for Profile Endpoints
 * Tests both GET and UPDATE profile functionality
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';
require_once 'middleware/authMiddleware.php';

use Controller\UserController;

echo "=== Profile Endpoints Test ===\n\n";

// Test 1: Test getProfile method directly
echo "Test 1: Testing getProfile method directly\n";
echo "----------------------------------------\n";

$controller = new UserController();

// Mock user data (simulating authenticated user)
$mockUser = [
    'user_id' => 1, // Replace with actual user ID from your database
    'role' => 'user'
];

try {
    $result = $controller->getProfile($mockUser);
    echo "getProfile result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "getProfile error: " . $e->getMessage() . "\n\n";
}

// Test 2: Test updateProfile method directly
echo "Test 2: Testing updateProfile method directly\n";
echo "-------------------------------------------\n";

$updateData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'phone' => '08012345678'
];

try {
    $result = $controller->updateProfile($updateData, $mockUser);
    echo "updateProfile result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "updateProfile error: " . $e->getMessage() . "\n\n";
}

// Test 3: Test with invalid data
echo "Test 3: Testing updateProfile with invalid data\n";
echo "-----------------------------------------------\n";

$invalidData = [
    'first_name' => '', // Empty first name
    'last_name' => 'Doe',
    'email' => 'invalid-email',
    'phone' => '123' // Too short
];

try {
    $result = $controller->updateProfile($invalidData, $mockUser);
    echo "updateProfile with invalid data result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "updateProfile with invalid data error: " . $e->getMessage() . "\n\n";
}

// Test 4: Test with duplicate email
echo "Test 4: Testing updateProfile with duplicate email\n";
echo "------------------------------------------------\n";

$duplicateEmailData = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'existing@example.com', // This should be an email that already exists
    'phone' => '08087654321'
];

try {
    $result = $controller->updateProfile($duplicateEmailData, $mockUser);
    echo "updateProfile with duplicate email result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "updateProfile with duplicate email error: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";

// Instructions for manual testing
echo "\n=== Manual Testing Instructions ===\n";
echo "1. Use Postman or similar tool to test the actual API endpoints\n";
echo "2. Test GET /api/get-profile.php with valid JWT token\n";
echo "3. Test PUT /api/update-profile.php with valid JWT token and data\n";
echo "4. Test with different user roles (user, merchant, rider)\n";
echo "5. Test validation errors (empty fields, invalid email, duplicate data)\n\n";

echo "=== Sample Request Body for update-profile.php ===\n";
echo "Method: PUT\n";
echo "Content-Type: application/json\n";
echo "Body (JSON Raw):\n";
echo json_encode([
    'first_name' => 'James',
    'last_name' => 'Sat',
    'email' => 'james.sat@gmail.com',
    'phone' => '+234808080808'
], JSON_PRETTY_PRINT) . "\n\n";

echo "=== Expected Response Format ===\n";
echo "GET /api/get-profile.php:\n";
echo json_encode([
    'status' => 'success',
    'message' => 'Profile retrieved successfully',
    'data' => [
        'user_id' => 1,
        'role' => 'user',
        'first_name' => 'James',
        'last_name' => 'Sat',
        'phone' => '+234808080808',
        'email' => 'james.sat@gmail.com',
        'address' => '123 Main Street, Lagos',
        'profile_picture' => null,
        'is_verified' => true,
        'status' => 'active',
        'created_at' => '2024-01-01 00:00:00',
        'updated_at' => '2024-01-01 00:00:00'
    ]
], JSON_PRETTY_PRINT) . "\n\n";

echo "PUT /api/update-profile.php:\n";
echo json_encode([
    'status' => 'success',
    'message' => 'Profile updated successfully',
    'data' => [
        'user_id' => 1,
        'role' => 'user',
        'first_name' => 'James',
        'last_name' => 'Sat',
        'phone' => '+234808080808',
        'email' => 'james.sat@gmail.com',
        'address' => '123 Main Street, Lagos',
        'profile_picture' => null,
        'is_verified' => true,
        'status' => 'active',
        'created_at' => '2024-01-01 00:00:00',
        'updated_at' => '2024-01-01 00:00:00'
    ]
], JSON_PRETTY_PRINT) . "\n";
?>
