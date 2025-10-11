<?php
// Simple test for rate limiting system
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Rate Limiting System Test ===\n\n";

try {
    // Test 1: Check if classes can be loaded
    echo "1. Testing class loading...\n";
    
    require_once '../../vendor/autoload.php';
    require_once '../config/cors.php';
    require_once '../middleware/rateLimitMiddleware.php';
    
    echo "   ✓ Autoloader loaded\n";
    echo "   ✓ CORS config loaded\n";
    echo "   ✓ Rate limit middleware loaded\n";
    
    // Test 2: Check if RateLimiter class exists
    echo "\n2. Testing RateLimiter class...\n";
    
    if (class_exists('Model\RateLimiter')) {
        echo "   ✓ RateLimiter class exists\n";
        
        $rateLimiter = new \Model\RateLimiter();
        echo "   ✓ RateLimiter instance created\n";
        
        // Test 3: Check database connection
        echo "\n3. Testing database connection...\n";
        
        try {
            $conn = (new \Config\Database())->getConnection();
            echo "   ✓ Database connection successful\n";
            
            // Test 4: Check if rate_limits table exists
            echo "\n4. Testing rate_limits table...\n";
            
            $stmt = $conn->query("SHOW TABLES LIKE 'rate_limits'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ rate_limits table exists\n";
                
                // Test 5: Test rate limit check
                echo "\n5. Testing rate limit functionality...\n";
                
                $testPhone = '2348123456789';
                $result = $rateLimiter->checkRateLimit($testPhone, 'phone', 'send_otp', 5, 3600, 3600);
                
                if ($result['allowed']) {
                    echo "   ✓ Rate limit check successful\n";
                    echo "   ✓ Current count: " . $result['current_count'] . "\n";
                    echo "   ✓ Max requests: " . $result['max_requests'] . "\n";
                    echo "   ✓ Remaining requests: " . $result['remaining_requests'] . "\n";
                } else {
                    echo "   ⚠ Rate limit exceeded: " . $result['message'] . "\n";
                }
                
            } else {
                echo "   ❌ rate_limits table does not exist\n";
                echo "   Please run the migration: SOURCE backend/migrations/create_rate_limits_table.sql;\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ❌ RateLimiter class not found\n";
    }
    
    // Test 6: Test RateLimiterController
    echo "\n6. Testing RateLimiterController...\n";
    
    if (class_exists('Controller\RateLimiterController')) {
        echo "   ✓ RateLimiterController class exists\n";
        
        $controller = new \Controller\RateLimiterController();
        echo "   ✓ RateLimiterController instance created\n";
        
        // Test rate limit check
        $testResult = $controller->checkOtpRateLimit('2348123456789', 'phone');
        echo "   ✓ OTP rate limit check completed\n";
        
        if ($testResult['allowed']) {
            echo "   ✓ Rate limit check passed\n";
        } else {
            echo "   ⚠ Rate limit check failed: " . $testResult['message'] . "\n";
        }
        
    } else {
        echo "   ❌ RateLimiterController class not found\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Rate limiting system is ready for testing!\n";
    echo "Next steps:\n";
    echo "1. Run the database migration if table doesn't exist\n";
    echo "2. Test with actual OTP endpoints\n";
    echo "3. Monitor rate limit behavior\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
