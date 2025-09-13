<?php
// Comprehensive test for OTP rate limiting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== OTP Rate Limiting Test Suite ===\n\n";

// Test configuration
$testPhone = '08123456789';
$testEmail = 'test@example.com';
$baseUrl = 'http://localhost/runnix/backend/api'; // Adjust as needed

echo "Test Configuration:\n";
echo "- Test Phone: {$testPhone}\n";
echo "- Test Email: {$testEmail}\n";
echo "- Base URL: {$baseUrl}\n\n";

// Function to make HTTP requests
function makeRequest($url, $data = null, $method = 'GET') {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Test 1: Test rate limit endpoint
echo "1. Testing rate limit endpoint...\n";
$testData = [
    'phone' => $testPhone,
    'email' => $testEmail,
    'purpose' => 'signup'
];

$result = makeRequest($baseUrl . '/test_rate_limit.php', $testData, 'POST');

if ($result['error']) {
    echo "   ❌ Request failed: " . $result['error'] . "\n";
} else {
    echo "   ✓ HTTP Code: " . $result['http_code'] . "\n";
    
    $responseData = json_decode($result['response'], true);
    if ($responseData) {
        echo "   ✓ Response parsed successfully\n";
        echo "   ✓ Status: " . $responseData['status'] . "\n";
        
        if (isset($responseData['rate_limit_result'])) {
            $rateLimit = $responseData['rate_limit_result'];
            echo "   ✓ Rate limit check completed\n";
            echo "   ✓ Allowed: " . ($rateLimit['allowed'] ? 'Yes' : 'No') . "\n";
            
            if ($rateLimit['allowed']) {
                echo "   ✓ Current count: " . $rateLimit['details']['phone']['current_count'] . "\n";
                echo "   ✓ Max requests: " . $rateLimit['details']['phone']['max_requests'] . "\n";
                echo "   ✓ Remaining requests: " . $rateLimit['details']['phone']['remaining_requests'] . "\n";
            } else {
                echo "   ⚠ Rate limited: " . $rateLimit['message'] . "\n";
            }
        }
    } else {
        echo "   ❌ Failed to parse response\n";
        echo "   Raw response: " . $result['response'] . "\n";
    }
}

echo "\n";

// Test 2: Test send_otp endpoint
echo "2. Testing send_otp endpoint...\n";
$otpData = [
    'phone' => $testPhone,
    'purpose' => 'signup'
];

$result = makeRequest($baseUrl . '/send_otp.php', $otpData, 'POST');

if ($result['error']) {
    echo "   ❌ Request failed: " . $result['error'] . "\n";
} else {
    echo "   ✓ HTTP Code: " . $result['http_code'] . "\n";
    
    $responseData = json_decode($result['response'], true);
    if ($responseData) {
        echo "   ✓ Response parsed successfully\n";
        echo "   ✓ Status: " . $responseData['status'] . "\n";
        
        if (isset($responseData['rate_limit'])) {
            $rateLimit = $responseData['rate_limit'];
            echo "   ✓ Rate limit info included\n";
            echo "   ✓ Allowed: " . ($rateLimit['allowed'] ? 'Yes' : 'No') . "\n";
            echo "   ✓ Current count: " . $rateLimit['current_count'] . "\n";
            echo "   ✓ Max requests: " . $rateLimit['max_requests'] . "\n";
            echo "   ✓ Remaining requests: " . $rateLimit['remaining_requests'] . "\n";
        }
        
        if ($responseData['status'] === 'success') {
            echo "   ✓ OTP sent successfully\n";
        } else {
            echo "   ⚠ OTP send failed: " . $responseData['message'] . "\n";
        }
    } else {
        echo "   ❌ Failed to parse response\n";
        echo "   Raw response: " . $result['response'] . "\n";
    }
}

echo "\n";

// Test 3: Test multiple requests to trigger rate limiting
echo "3. Testing multiple requests to trigger rate limiting...\n";

for ($i = 1; $i <= 5; $i++) {
    echo "   Request {$i}/5...\n";
    
    $result = makeRequest($baseUrl . '/send_otp.php', $otpData, 'POST');
    
    if ($result['error']) {
        echo "     ❌ Request failed: " . $result['error'] . "\n";
        continue;
    }
    
    $responseData = json_decode($result['response'], true);
    if ($responseData) {
        echo "     ✓ Status: " . $responseData['status'] . "\n";
        
        if (isset($responseData['rate_limit'])) {
            $rateLimit = $responseData['rate_limit'];
            echo "     ✓ Allowed: " . ($rateLimit['allowed'] ? 'Yes' : 'No') . "\n";
            echo "     ✓ Current count: " . $rateLimit['current_count'] . "\n";
            echo "     ✓ Remaining requests: " . $rateLimit['remaining_requests'] . "\n";
            
            if (!$rateLimit['allowed']) {
                echo "     🚫 Rate limit triggered!\n";
                break;
            }
        }
    }
    
    // Small delay between requests
    sleep(1);
}

echo "\n";

// Test 4: Test password reset endpoint
echo "4. Testing password reset endpoint...\n";
$resetData = [
    'phone' => $testPhone
];

$result = makeRequest($baseUrl . '/request_password_reset.php', $resetData, 'POST');

if ($result['error']) {
    echo "   ❌ Request failed: " . $result['error'] . "\n";
} else {
    echo "   ✓ HTTP Code: " . $result['http_code'] . "\n";
    
    $responseData = json_decode($result['response'], true);
    if ($responseData) {
        echo "   ✓ Response parsed successfully\n";
        echo "   ✓ Status: " . $responseData['status'] . "\n";
        
        if (isset($responseData['rate_limit'])) {
            $rateLimit = $responseData['rate_limit'];
            echo "   ✓ Rate limit info included\n";
            echo "   ✓ Allowed: " . ($rateLimit['allowed'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "   ❌ Failed to parse response\n";
        echo "   Raw response: " . $result['response'] . "\n";
    }
}

echo "\n=== Test Summary ===\n";
echo "Rate limiting tests completed!\n";
echo "Check the results above to verify:\n";
echo "1. Rate limit endpoint works\n";
echo "2. OTP endpoints include rate limiting\n";
echo "3. Multiple requests trigger rate limiting\n";
echo "4. Different endpoints have different limits\n";
echo "\nNext steps:\n";
echo "- Run the database migration if needed\n";
echo "- Test with real phone numbers\n";
echo "- Monitor rate limit behavior in production\n";
