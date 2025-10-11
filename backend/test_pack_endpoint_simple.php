<?php
// Simple test to check if the pack endpoint is accessible

echo "Testing pack endpoint accessibility...\n\n";

// Test 1: Check if the file exists and is readable
$endpointFile = __DIR__ . '/api/get_packby_id.php';
echo "1. Checking if endpoint file exists: ";
if (file_exists($endpointFile)) {
    echo "YES\n";
    echo "   File is readable: " . (is_readable($endpointFile) ? "YES" : "NO") . "\n";
} else {
    echo "NO\n";
}

// Test 2: Check if we can include the required files
echo "\n2. Testing file includes:\n";
try {
    require_once '../../vendor/autoload.php';
    echo "   ✓ vendor/autoload.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ vendor/autoload.php failed: " . $e->getMessage() . "\n";
}

try {
    require_once '../config/cors.php';
    echo "   ✓ cors.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ cors.php failed: " . $e->getMessage() . "\n";
}

try {
    require_once '../middleware/authMiddleware.php';
    echo "   ✓ authMiddleware.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ authMiddleware.php failed: " . $e->getMessage() . "\n";
}

// Test 3: Check if PackController class exists
echo "\n3. Testing PackController class:\n";
try {
    $controller = new \Controller\PackController();
    echo "   ✓ PackController instantiated successfully\n";
    
    // Check if getPackById method exists
    if (method_exists($controller, 'getPackById')) {
        echo "   ✓ getPackById method exists\n";
    } else {
        echo "   ✗ getPackById method does not exist\n";
    }
} catch (Exception $e) {
    echo "   ✗ PackController failed: " . $e->getMessage() . "\n";
}

// Test 4: Check if Pack model exists
echo "\n4. Testing Pack model:\n";
try {
    $packModel = new \Model\Pack();
    echo "   ✓ Pack model instantiated successfully\n";
    
    // Check if getPackById method exists
    if (method_exists($packModel, 'getPackById')) {
        echo "   ✓ Pack model getPackById method exists\n";
    } else {
        echo "   ✗ Pack model getPackById method does not exist\n";
    }
} catch (Exception $e) {
    echo "   ✗ Pack model failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
