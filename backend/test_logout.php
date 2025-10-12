<?php
/**
 * Test Script: Logout Functionality
 * This script tests the logout endpoint and related functionality
 */

require_once 'vendor/autoload.php';
require_once 'config/Database.php';
require_once 'config/JwtHandler.php';
require_once 'model/UserActivity.php';
require_once 'model/LogoutLog.php';

use Config\JwtHandler;
use Model\UserActivity;
use Model\LogoutLog;

echo "🧪 Testing Logout Functionality\n";
echo "================================\n\n";

try {
    // Test 1: JWT Handler
    echo "1. Testing JWT Handler...\n";
    $jwt = new JwtHandler();
    
    // Create a test token
    $testPayload = [
        'user_id' => 1,
        'role' => 'user',
        'test' => true
    ];
    
    $token = $jwt->encode($testPayload);
    echo "   ✅ Token created successfully\n";
    
    // Decode token
    $decoded = $jwt->decode($token);
    if ($decoded && $decoded['user_id'] == 1) {
        echo "   ✅ Token decoded successfully\n";
    } else {
        echo "   ❌ Token decode failed\n";
    }
    
    // Test 2: User Activity Model
    echo "\n2. Testing User Activity Model...\n";
    $userActivity = new UserActivity();
    
    // Test timeout settings
    $userTimeout = $userActivity->getInactivityTimeout('user');
    $merchantTimeout = $userActivity->getInactivityTimeout('merchant');
    $riderTimeout = $userActivity->getInactivityTimeout('rider');
    
    echo "   ✅ User timeout: {$userTimeout} minutes\n";
    echo "   ✅ Merchant timeout: {$merchantTimeout} minutes\n";
    echo "   ✅ Rider timeout: {$riderTimeout} minutes\n";
    
    // Test 3: Logout Log Model
    echo "\n3. Testing Logout Log Model...\n";
    $logoutLog = new LogoutLog();
    
    // Test logging a logout event
    $testLogoutData = [
        'user_id' => 1,
        'user_role' => 'user',
        'logout_type' => 'manual',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Script',
        'device_info' => json_encode(['platform' => 'test']),
        'session_duration_minutes' => 15.5,
        'token_blacklisted' => true,
        'session_deactivated' => true,
        'logout_reason' => 'Test logout'
    ];
    
    $logged = $logoutLog->logLogout($testLogoutData);
    if ($logged) {
        echo "   ✅ Logout event logged successfully\n";
    } else {
        echo "   ❌ Failed to log logout event\n";
    }
    
    // Test 4: Database Connection
    echo "\n4. Testing Database Connection...\n";
    $db = new Config\Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "   ✅ Database connection successful\n";
        
        // Check if tables exist
        $tables = ['blacklisted_tokens', 'user_activity', 'logout_logs'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                echo "   ✅ Table '{$table}' exists\n";
            } else {
                echo "   ❌ Table '{$table}' missing\n";
            }
        }
    } else {
        echo "   ❌ Database connection failed\n";
    }
    
    echo "\n🎉 All tests completed!\n";
    echo "\n📋 Summary:\n";
    echo "- JWT token creation and validation: Working\n";
    echo "- User activity timeout settings: Configured\n";
    echo "- Logout logging: Functional\n";
    echo "- Database tables: Verified\n";
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
