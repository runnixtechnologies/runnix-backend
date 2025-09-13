<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';
require_once '../middleware/rateLimitMiddleware.php';

use Controller\UserController;
use function Middleware\authenticateRequest;
use function Middleware\checkOtpRateLimit;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// Authenticate the user
$user = authenticateRequest();

// Get request data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$currentPassword = $data['current_password'] ?? null;
$newPassword = $data['new_password'] ?? null;
$confirmPassword = $data['confirm_password'] ?? null;

if (!$currentPassword || !$newPassword || !$confirmPassword) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Current password, new password, and confirm password are required'
    ]);
    exit;
}

// Validate password confirmation
if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'New password and confirm password do not match'
    ]);
    exit;
}

// Validate new password strength
$passwordValidation = validatePasswordStrength($newPassword);
if (!$passwordValidation['valid']) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $passwordValidation['message']
    ]);
    exit;
}

// Check rate limit for password change attempts
$rateLimitResult = checkOtpRateLimit($user['phone'] ?? $user['email'], $user['email'] ? 'email' : 'phone', 'password_change');

// If rate limit check passed, proceed with password change
$userController = new UserController();
$response = $userController->changePassword($user['user_id'], $currentPassword, $newPassword);

// Add rate limit info to response
if ($response['status'] === 'success') {
    $response['rate_limit'] = [
        'allowed' => true,
        'current_count' => $rateLimitResult['details']['phone']['current_count'] ?? $rateLimitResult['details']['email']['current_count'] ?? 0,
        'max_requests' => $rateLimitResult['details']['phone']['max_requests'] ?? $rateLimitResult['details']['email']['max_requests'] ?? 0,
        'remaining_requests' => $rateLimitResult['details']['phone']['remaining_requests'] ?? $rateLimitResult['details']['email']['remaining_requests'] ?? 0
    ];
}

echo json_encode($response);

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Minimum length
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    // Maximum length
    if (strlen($password) > 128) {
        $errors[] = 'Password must be less than 128 characters';
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    // Check for at least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Check for common weak passwords
    $weakPasswords = [
        'password', 'password123', '12345678', 'qwerty123', 'admin123',
        'letmein', 'welcome123', 'monkey123', 'dragon123', 'master123'
    ];
    
    if (in_array(strtolower($password), $weakPasswords)) {
        $errors[] = 'Password is too common. Please choose a stronger password';
    }
    
    // Check for repeated characters
    if (preg_match('/(.)\1{2,}/', $password)) {
        $errors[] = 'Password cannot contain more than 2 consecutive identical characters';
    }
    
    // Check for sequential characters
    if (preg_match('/123|234|345|456|567|678|789|890|abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz/i', $password)) {
        $errors[] = 'Password cannot contain sequential characters';
    }
    
    if (empty($errors)) {
        return ['valid' => true, 'message' => 'Password is strong'];
    } else {
        return ['valid' => false, 'message' => implode('. ', $errors)];
    }
}
