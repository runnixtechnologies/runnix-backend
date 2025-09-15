<?php

/**
 * Test login functionality without FCM configuration
 * This script tests if the login API works when FCM is not configured
 */

require_once '../vendor/autoload.php';
require_once 'config/cors.php';

use Controller\UserController;

echo "=== Testing Login Without FCM Configuration ===\n\n";

try {
    // Test 1: Create UserController (this should not throw an exception now)
    echo "1. Testing UserController instantiation...\n";
    $userController = new UserController();
    echo "   ✅ UserController created successfully\n\n";
    
    // Test 2: Test login with sample data
    echo "2. Testing login functionality...\n";
    $loginData = [
        'identifier' => 'test@example.com',
        'password' => 'testpassword'
    ];
    
    $result = $userController->login($loginData);
    echo "   📄 Login result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "✅ Test completed successfully!\n";
    echo "The login API works even when FCM is not configured.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== End of Test ===\n";

?>
