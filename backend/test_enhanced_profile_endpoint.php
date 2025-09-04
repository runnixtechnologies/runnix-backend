<?php
/**
 * Test script to verify the enhanced profile endpoint with merchant business information
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\User;
use Model\Store;
use Config\Database;

echo "=== Testing Enhanced Profile Endpoint ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Test the UserController getProfile method directly
    echo "\n1. Testing UserController getProfile method...\n";
    $userController = new \Controller\UserController();
    
    // Get a merchant user to test with
    $query = "SELECT u.id, u.role, u.email, u.phone, s.id as store_id 
              FROM users u 
              LEFT JOIN stores s ON u.id = s.user_id 
              WHERE u.role = 'merchant' 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$merchant) {
        echo "❌ No merchant users found in database\n";
        exit;
    }
    
    echo "Testing with merchant: ID=" . $merchant['id'] . ", Email=" . $merchant['email'] . "\n";
    echo "Store ID: " . ($merchant['store_id'] ?: 'No store') . "\n";
    
    // Mock user data for the controller
    $user = [
        'user_id' => $merchant['id'],
        'role' => $merchant['role']
    ];
    
    // Test the getProfile method
    $response = $userController->getProfile($user);
    
    echo "\nProfile response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    
    // Verify the response structure
    if ($response['status'] === 'success' && isset($response['data'])) {
        $profileData = $response['data'];
        
        echo "\n2. Verifying profile structure...\n";
        
        // Check basic profile fields
        $requiredFields = ['user_id', 'role', 'first_name', 'last_name', 'phone', 'email', 'address', 'profile_picture'];
        foreach ($requiredFields as $field) {
            if (isset($profileData[$field])) {
                echo "  ✅ $field: " . ($profileData[$field] ?: 'empty') . "\n";
            } else {
                echo "  ❌ $field: missing\n";
            }
        }
        
        // Check merchant-specific fields
        if ($profileData['role'] === 'merchant') {
            echo "\n3. Verifying merchant business information...\n";
            
            if (isset($profileData['business']) && $profileData['business'] !== null) {
                echo "  ✅ Business information present\n";
                
                $businessFields = [
                    'store_name' => 'Store Name',
                    'business_address' => 'Business Address', 
                    'business_email' => 'Business Email',
                    'business_phone' => 'Business Phone',
                    'business_registration_number' => 'Business Registration Number',
                    'business_logo' => 'Business Logo',
                    'business_url' => 'Business URL',
                    'store_id' => 'Store ID',
                    'store_type_id' => 'Store Type ID',
                    'operating_hours' => 'Operating Hours'
                ];
                
                foreach ($businessFields as $field => $label) {
                    if (isset($profileData['business'][$field])) {
                        $value = $profileData['business'][$field];
                        if ($field === 'operating_hours' && is_array($value)) {
                            echo "    ✅ $label: " . count($value) . " days configured\n";
                        } else {
                            echo "    ✅ $label: " . ($value ?: 'empty') . "\n";
                        }
                    } else {
                        echo "    ❌ $label: missing\n";
                    }
                }
                
                // Test operating hours structure
                if (isset($profileData['business']['operating_hours']) && $profileData['business']['operating_hours']) {
                    echo "\n4. Operating hours structure:\n";
                    $operatingHours = $profileData['business']['operating_hours'];
                    
                    if (isset($operatingHours['business_24_7'])) {
                        echo "  ✅ 24/7 Business: " . ($operatingHours['business_24_7'] ? 'Yes' : 'No') . "\n";
                    }
                    
                    if (isset($operatingHours['operating_hours']) && is_array($operatingHours['operating_hours'])) {
                        echo "  ✅ Operating hours for " . count($operatingHours['operating_hours']) . " days\n";
                        
                        foreach ($operatingHours['operating_hours'] as $day => $hours) {
                            if ($hours['is_closed']) {
                                echo "    - $day: Closed\n";
                            } elseif ($hours['is_24hrs']) {
                                echo "    - $day: 24 Hours\n";
                            } else {
                                echo "    - $day: " . $hours['open_time'] . " - " . $hours['close_time'] . "\n";
                            }
                        }
                    }
                }
                
            } else {
                echo "  ⚠️  Business information is null (merchant without store setup)\n";
            }
        }
        
        echo "\n✅ Profile endpoint test completed successfully!\n";
        
    } else {
        echo "❌ Profile endpoint returned error: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
    
    // Test with a non-merchant user
    echo "\n5. Testing with non-merchant user...\n";
    $query = "SELECT id, role, email FROM users WHERE role != 'merchant' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $regularUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($regularUser) {
        $user = [
            'user_id' => $regularUser['id'],
            'role' => $regularUser['role']
        ];
        
        $response = $userController->getProfile($user);
        
        if ($response['status'] === 'success' && isset($response['data'])) {
            $profileData = $response['data'];
            
            if (!isset($profileData['business'])) {
                echo "  ✅ Non-merchant user correctly has no business information\n";
            } else {
                echo "  ❌ Non-merchant user incorrectly has business information\n";
            }
        }
    } else {
        echo "  ⚠️  No non-merchant users found to test\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
