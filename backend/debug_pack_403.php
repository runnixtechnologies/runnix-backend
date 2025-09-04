<?php
// Debug script for pack 403 error

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config/cors.php';
require_once 'middleware/authMiddleware.php';

use Controller\PackController;
use Model\Pack;
use Model\Store;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

echo "=== PACK 403 DEBUG SCRIPT ===\n\n";

// Test parameters - REPLACE THESE WITH YOUR ACTUAL VALUES
$packId = 1; // Replace with the actual pack ID you're trying to access
$testToken = 'your_jwt_token_here'; // Replace with your actual JWT token

echo "Testing with Pack ID: $packId\n";
echo "Testing with Token: " . substr($testToken, 0, 20) . "...\n\n";

try {
    // Step 1: Test authentication
    echo "1. Testing Authentication...\n";
    $_GET['token'] = $testToken; // Set token for GET request
    $user = authenticateRequest();
    echo "   ✓ Authentication successful\n";
    echo "   User data: " . json_encode($user) . "\n\n";
    
    // Step 2: Test store lookup
    echo "2. Testing Store Lookup...\n";
    $storeModel = new Store();
    $store = $storeModel->getStoreByUserId($user['user_id']);
    if ($store) {
        echo "   ✓ Store found\n";
        echo "   Store data: " . json_encode($store) . "\n";
        echo "   Store ID: " . $store['id'] . "\n\n";
    } else {
        echo "   ✗ Store not found for user_id: " . $user['user_id'] . "\n\n";
        exit;
    }
    
    // Step 3: Test pack lookup
    echo "3. Testing Pack Lookup...\n";
    $packModel = new Pack();
    $pack = $packModel->getPackById($packId);
    if ($pack) {
        echo "   ✓ Pack found\n";
        echo "   Pack data: " . json_encode($pack) . "\n";
        echo "   Pack store_id: " . $pack['store_id'] . "\n";
        echo "   User store_id: " . $store['id'] . "\n\n";
        
        // Step 4: Test ownership check
        echo "4. Testing Ownership Check...\n";
        if ($pack['store_id'] == $store['id']) {
            echo "   ✓ Ownership check passed\n";
            echo "   Pack belongs to user's store\n\n";
        } else {
            echo "   ✗ Ownership check failed\n";
            echo "   Pack belongs to store " . $pack['store_id'] . " but user belongs to store " . $store['id'] . "\n\n";
        }
    } else {
        echo "   ✗ Pack not found with ID: $packId\n\n";
    }
    
    // Step 5: Test full controller method
    echo "5. Testing Full Controller Method...\n";
    $controller = new PackController();
    $response = $controller->getPackById($packId, $user);
    echo "   Controller response: " . json_encode($response) . "\n\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

echo "=== DEBUG COMPLETE ===\n";
?>
