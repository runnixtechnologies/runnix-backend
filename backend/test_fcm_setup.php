<?php
/**
 * Test script for FCM setup verification
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

echo "=== FCM SETUP TEST ===\n\n";

// Test 1: Check if Firebase SDK is installed
echo "1. Checking Firebase SDK installation...\n";
if (class_exists('Kreait\Firebase\Factory')) {
    echo "   ✅ Firebase SDK is installed\n";
} else {
    echo "   ❌ Firebase SDK is NOT installed\n";
    echo "   Run: composer require kreait/firebase-php\n\n";
    exit;
}

// Test 2: Check environment configuration
echo "\n2. Checking environment configuration...\n";

// Load .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        echo "   ✅ .env file found and loaded\n";
    } catch (\Exception $e) {
        echo "   ❌ .env file found but has errors: " . $e->getMessage() . "\n";
        echo "   Please fix the .env file format\n";
    }
} else {
    echo "   ⚠️  .env file not found\n";
    echo "   Create .env file with FCM configuration\n";
}

// Check FCM configuration
$fcmProjectId = $_ENV['FCM_PROJECT_ID'] ?? null;
$fcmServerKey = $_ENV['FCM_SERVER_KEY'] ?? null;
$fcmServiceAccountPath = $_ENV['FCM_SERVICE_ACCOUNT_PATH'] ?? null;

echo "   FCM_PROJECT_ID: " . ($fcmProjectId ? "✅ Set" : "❌ Missing") . "\n";
echo "   FCM_SERVER_KEY: " . ($fcmServerKey ? "✅ Set" : "❌ Missing") . "\n";
echo "   FCM_SERVICE_ACCOUNT_PATH: " . ($fcmServiceAccountPath ? "✅ Set" : "❌ Missing") . "\n";

// Test 3: Check service account file
if ($fcmServiceAccountPath && file_exists($fcmServiceAccountPath)) {
    echo "   ✅ Service account file exists\n";
    
    // Validate JSON
    $serviceAccount = json_decode(file_get_contents($fcmServiceAccountPath), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✅ Service account JSON is valid\n";
    } else {
        echo "   ❌ Service account JSON is invalid\n";
    }
} else {
    echo "   ⚠️  Service account file not found\n";
}

// Test 4: Test FCM configuration class
echo "\n3. Testing FCM configuration class...\n";
try {
    $config = \Config\FCMConfig::getInstance();
    $configData = $config->getConfig();
    
    echo "   Configuration loaded: " . ($configData['configured'] ? "✅ Yes" : "❌ No") . "\n";
    echo "   Project ID: " . ($configData['project_id'] ?: 'Not set') . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error loading FCM config: " . $e->getMessage() . "\n";
}

// Test 5: Test FCM service initialization
echo "\n4. Testing FCM service initialization...\n";
try {
    $fcmService = new \Service\FCMService();
    echo "   ✅ FCM service initialized successfully\n";
} catch (Exception $e) {
    echo "   ❌ FCM service initialization failed: " . $e->getMessage() . "\n";
}

// Test 6: Test FCM token model
echo "\n5. Testing FCM token model...\n";
try {
    $fcmTokenModel = new \Model\FCMToken();
    echo "   ✅ FCM token model initialized successfully\n";
    
    // Test database connection
    $stats = $fcmTokenModel->getTokenStats();
    echo "   ✅ Database connection working\n";
    echo "   Token stats: " . json_encode($stats) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ FCM token model failed: " . $e->getMessage() . "\n";
}

// Test 7: Test FCM controller
echo "\n6. Testing FCM controller...\n";
try {
    $fcmController = new \Controller\FCMController();
    echo "   ✅ FCM controller initialized successfully\n";
} catch (Exception $e) {
    echo "   ❌ FCM controller failed: " . $e->getMessage() . "\n";
}

echo "\n=== SETUP SUMMARY ===\n";
echo "If all tests pass, your FCM setup is ready!\n";
echo "Next steps:\n";
echo "1. Configure your .env file with Firebase credentials\n";
echo "2. Test the API endpoints with your mobile app\n";
echo "3. Send test notifications\n\n";

echo "=== API ENDPOINTS ===\n";
echo "Register Token: POST /api/fcm_register_token.php\n";
echo "Send Notification: POST /api/fcm_send_notification.php\n";
echo "Send Bulk Notification: POST /api/fcm_send_bulk_notification.php\n\n";

echo "=== TEST COMPLETE ===\n";
?>
