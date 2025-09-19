<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\UserController;
use Model\Store;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
    
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only merchants can access this endpoint']);
        exit;
    }
    
    $userController = new UserController();
    $storeModel = new Store();
    
    // Get profile data (same as get-profile.php)
    $profileResponse = $userController->getProfile($user);
    
    // Get store data directly
    $storeData = $storeModel->getStoreByUserId($user['user_id']);
    
    // Get merchant store data
    $userModel = new \Model\User();
    $merchantStoreData = $userModel->getMerchantStore($user['user_id']);
    
    echo json_encode([
        'status' => 'success',
        'debug_info' => [
            'user_id' => $user['user_id'],
            'profile_response' => $profileResponse,
            'store_data_direct' => $storeData,
            'merchant_store_data' => $merchantStoreData,
            'biz_logo_from_store' => $storeData['biz_logo'] ?? 'NOT_FOUND',
            'biz_logo_from_merchant_store' => $merchantStoreData['biz_logo'] ?? 'NOT_FOUND',
            'biz_logo_from_profile' => $profileResponse['data']['biz_logo'] ?? 'NOT_FOUND'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Debug store profile error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage()
    ]);
}
?>
