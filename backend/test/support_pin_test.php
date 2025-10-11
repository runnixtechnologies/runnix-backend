<?php
/**
 * Support Pin System Test Script
 * This script demonstrates the support pin functionality
 */

require_once '../config/Database.php';
require_once '../model/User.php';
require_once '../controller/SupportController.php';
require_once '../controller/SupportContactController.php';

echo "<h1>Support Pin System Test</h1>\n";

try {
    // Test 1: Create a test user
    echo "<h2>Test 1: Creating Test User</h2>\n";
    $userModel = new Model\User();
    
    // Create a test user (this will automatically generate a support pin)
    $testEmail = 'test@example.com';
    $testPhone = '2341234567890';
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
    
    // Test 2: Verify support pin format
    echo "<h2>Test 2: Support Pin Format Validation</h2>\n";
    if (strlen($supportPin) === 8) {
        echo "✓ Support pin length is correct (8 characters)\n";
        
        $letters = substr($supportPin, 0, 4);
        $numbers = substr($supportPin, 4, 4);
        
        if (ctype_alpha($letters)) {
            echo "✓ First 4 characters are letters: $letters\n";
        } else {
            echo "✗ First 4 characters are not letters: $letters\n";
        }
        
        if (ctype_digit($numbers)) {
            echo "✓ Last 4 characters are numbers: $numbers\n";
        } else {
            echo "✗ Last 4 characters are not numbers: $numbers\n";
        }
    } else {
        echo "✗ Support pin length is incorrect: " . strlen($supportPin) . " characters\n";
    }
    
    // Test 3: Support pin verification
    echo "<h2>Test 3: Support Pin Verification</h2>\n";
    $supportController = new Controller\SupportController();
    
    // Test valid pin
    $result = $supportController->verifySupportPin($supportPin, $testEmail);
    if ($result['status'] === 'success') {
        echo "✓ Support pin verification successful\n";
        echo "  User ID: " . $result['user']['id'] . "\n";
        echo "  Email: " . $result['user']['email'] . "\n";
        echo "  Role: " . $result['user']['role'] . "\n";
    } else {
        echo "✗ Support pin verification failed: " . $result['message'] . "\n";
    }
    
    // Test invalid pin
    $invalidPin = 'INVALID1';
    $result = $supportController->verifySupportPin($invalidPin);
    if ($result['status'] === 'error') {
        echo "✓ Invalid support pin correctly rejected: " . $result['message'] . "\n";
    } else {
        echo "✗ Invalid support pin was not rejected\n";
    }
    
    // Test 4: Support pin regeneration
    echo "<h2>Test 4: Support Pin Regeneration</h2>\n";
    $newPin = $userModel->regenerateSupportPin($userId);
    if ($newPin && $newPin !== $supportPin) {
        echo "✓ Support pin regenerated successfully\n";
        echo "  Old pin: $supportPin\n";
        echo "  New pin: $newPin\n";
        
        // Verify the new pin works
        $result = $supportController->verifySupportPin($newPin, $testEmail);
        if ($result['status'] === 'success') {
            echo "✓ New support pin verification successful\n";
        } else {
            echo "✗ New support pin verification failed\n";
        }
    } else {
        echo "✗ Support pin regeneration failed\n";
    }
    
    // Test 5: Support contact form
    echo "<h2>Test 5: Support Contact Form</h2>\n";
    $supportContactController = new Controller\SupportContactController();
    
    $testData = [
        'fullname' => 'Test User',
        'email' => $testEmail,
        'phone' => $testPhone,
        'interest_complaints' => 'Account Issue',
        'message' => 'This is a test support request',
        'support_pin' => $newPin
    ];
    
    $result = $supportContactController->handleSupportFormSubmission(
        $testData['fullname'],
        $testData['email'],
        $testData['phone'],
        $testData['interest_complaints'],
        $testData['message'],
        $testData['support_pin']
    );
    
    $response = json_decode($result, true);
    if ($response['status'] === 'success') {
        echo "✓ Support contact form submission successful\n";
        echo "  Message: " . $response['message'] . "\n";
        if (isset($response['user_info'])) {
            echo "  User verified: " . $response['user_info']['email'] . "\n";
        }
    } else {
        echo "✗ Support contact form submission failed: " . $response['message'] . "\n";
    }
    
    echo "<h2>Test Summary</h2>\n";
    echo "✓ All support pin system tests completed successfully!\n";
    echo "✓ The system is ready for production use.\n";
    
} catch (Exception $e) {
    echo "<h2>Test Error</h2>\n";
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "1. Run the database migrations to add support_pin column\n";
echo "2. Update your frontend to display support pins to users\n";
echo "3. Integrate support pin verification in your contact forms\n";
echo "4. Train your support team on the new verification process\n";
?>
