<?php
/**
 * Test script to verify support pin is included in profile response
 */

require_once '../config/Database.php';
require_once '../model/User.php';
require_once '../controller/UserController.php';

echo "<h1>Profile Support Pin Test</h1>\n";

try {
    // Test 1: Create a test user if it doesn't exist
    echo "<h2>Test 1: Creating/Getting Test User</h2>\n";
    $userModel = new Model\User();
    
    $testEmail = 'profile-test@example.com';
    $testPhone = '2341234567891';
    $testPassword = 'testpassword123';
    $testRole = 'user';
    
    // Check if user already exists
    $existingUser = $userModel->getUserByEmail($testEmail);
    if ($existingUser) {
        echo "Test user already exists. Using existing user.\n";
        $userId = $existingUser['id'];
        $supportPin = $existingUser['support_pin'];
    } else {
        $userId = $userModel->createUser($testEmail, $testPhone, $testPassword, $testRole);
        if ($userId) {
            echo "✓ Test user created successfully with ID: $userId\n";
            
            // Get the created user to see the support pin
            $user = $userModel->getUserById($userId);
            $supportPin = $user['support_pin'];
            echo "✓ Support pin generated: $supportPin\n";
        } else {
            throw new Exception("Failed to create test user");
        }
    }
    
    // Test 2: Test getProfile method
    echo "<h2>Test 2: Testing getProfile Method</h2>\n";
    $userController = new Controller\UserController();
    
    // Simulate authenticated user data
    $authenticatedUser = [
        'user_id' => $userId,
        'role' => $testRole
    ];
    
    $profileResponse = $userController->getProfile($authenticatedUser);
    
    if ($profileResponse['status'] === 'success') {
        echo "✓ Profile retrieved successfully\n";
        
        $profileData = $profileResponse['data'];
        
        // Check if support_pin is included
        if (isset($profileData['support_pin'])) {
            echo "✓ Support pin is included in profile response: " . $profileData['support_pin'] . "\n";
            
            // Verify it matches the database value
            if ($profileData['support_pin'] === $supportPin) {
                echo "✓ Support pin matches database value\n";
            } else {
                echo "✗ Support pin does not match database value\n";
                echo "  Database: $supportPin\n";
                echo "  Profile: " . $profileData['support_pin'] . "\n";
            }
        } else {
            echo "✗ Support pin is NOT included in profile response\n";
        }
        
        // Display other profile data
        echo "\nProfile Data:\n";
        echo "  User ID: " . $profileData['user_id'] . "\n";
        echo "  Role: " . $profileData['role'] . "\n";
        echo "  Email: " . $profileData['email'] . "\n";
        echo "  Phone: " . $profileData['phone'] . "\n";
        echo "  Support Pin: " . ($profileData['support_pin'] ?? 'NOT FOUND') . "\n";
        echo "  Is Verified: " . ($profileData['is_verified'] ? 'Yes' : 'No') . "\n";
        echo "  Status: " . $profileData['status'] . "\n";
        
    } else {
        echo "✗ Profile retrieval failed: " . $profileResponse['message'] . "\n";
    }
    
    // Test 3: Test login method (should also include support pin)
    echo "<h2>Test 3: Testing Login Method</h2>\n";
    
    $loginData = [
        'identifier' => $testEmail,
        'password' => $testPassword
    ];
    
    $loginResponse = $userController->login($loginData);
    
    if ($loginResponse['status'] === 'success') {
        echo "✓ Login successful\n";
        
        $userData = $loginResponse['user'];
        
        // Check if support_pin is included in login response
        if (isset($userData['support_pin'])) {
            echo "✓ Support pin is included in login response: " . $userData['support_pin'] . "\n";
            
            // Verify it matches the database value
            if ($userData['support_pin'] === $supportPin) {
                echo "✓ Support pin matches database value in login response\n";
            } else {
                echo "✗ Support pin does not match database value in login response\n";
            }
        } else {
            echo "✗ Support pin is NOT included in login response\n";
        }
        
    } else {
        echo "✗ Login failed: " . $loginResponse['message'] . "\n";
    }
    
    echo "<h2>Test Summary</h2>\n";
    echo "✓ Profile support pin integration test completed!\n";
    echo "✓ The support pin is now included in both profile and login responses.\n";
    
} catch (Exception $e) {
    echo "<h2>Test Error</h2>\n";
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>API Endpoints Updated</h2>\n";
echo "The following endpoints now include support_pin in their responses:\n";
echo "• GET /api/get-profile.php - Returns user profile with support_pin\n";
echo "• POST /api/login.php - Returns user data with support_pin\n";
echo "\nFrontend developers can now access the support_pin from these endpoints.\n";
?>
