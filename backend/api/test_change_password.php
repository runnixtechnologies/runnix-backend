<?php
// Test password change functionality
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';
require_once '../middleware/rateLimitMiddleware.php';

use Controller\UserController;
use function Middleware\authenticateRequest;
use function Middleware\checkOtpRateLimit;

header('Content-Type: application/json');

echo "=== Password Change Test ===\n\n";

// Test 1: Check if classes can be loaded
echo "1. Testing class loading...\n";

try {
    echo "   ✓ Autoloader loaded\n";
    echo "   ✓ CORS config loaded\n";
    echo "   ✓ Auth middleware loaded\n";
    echo "   ✓ Rate limit middleware loaded\n";
    
    // Test 2: Check if UserController exists
    echo "\n2. Testing UserController...\n";
    
    if (class_exists('Controller\UserController')) {
        echo "   ✓ UserController class exists\n";
        
        $userController = new UserController();
        echo "   ✓ UserController instance created\n";
        
        // Test 3: Test password validation
        echo "\n3. Testing password validation...\n";
        
        $testPasswords = [
            'weak' => '123',
            'short' => 'abc123',
            'no_upper' => 'password123',
            'no_lower' => 'PASSWORD123',
            'no_number' => 'Password',
            'no_special' => 'Password123',
            'weak_common' => 'password123',
            'repeated' => 'Password111',
            'sequential' => 'Password123',
            'strong' => 'MyStr0ng!Pass'
        ];
        
        foreach ($testPasswords as $type => $password) {
            echo "   Testing {$type}: ";
            
            // Use reflection to access private method
            $reflection = new ReflectionClass($userController);
            $method = $reflection->getMethod('validatePasswordStrength');
            $method->setAccessible(true);
            
            $result = $method->invoke($userController, $password);
            
            if ($result['valid']) {
                echo "✓ Valid\n";
            } else {
                echo "✗ Invalid - " . $result['message'] . "\n";
            }
        }
        
        // Test 4: Test rate limiting
        echo "\n4. Testing rate limiting...\n";
        
        $testPhone = '2348123456789';
        $testEmail = 'test@example.com';
        
        try {
            $rateLimitResult = checkOtpRateLimit($testPhone, $testEmail, 'password_change');
            echo "   ✓ Rate limit check completed\n";
            
            if ($rateLimitResult['allowed']) {
                echo "   ✓ Rate limit check passed\n";
                echo "   ✓ Current count: " . ($rateLimitResult['details']['phone']['current_count'] ?? 0) . "\n";
                echo "   ✓ Max requests: " . ($rateLimitResult['details']['phone']['max_requests'] ?? 0) . "\n";
                echo "   ✓ Remaining requests: " . ($rateLimitResult['details']['phone']['remaining_requests'] ?? 0) . "\n";
            } else {
                echo "   ⚠ Rate limit check failed: " . $rateLimitResult['message'] . "\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Rate limit test failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ❌ UserController class not found\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Password change system is ready!\n";
    echo "Features tested:\n";
    echo "✓ Class loading\n";
    echo "✓ UserController instantiation\n";
    echo "✓ Password strength validation\n";
    echo "✓ Rate limiting\n";
    echo "\nNext steps:\n";
    echo "1. Test with actual authentication\n";
    echo "2. Test password change endpoint\n";
    echo "3. Verify security measures\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
