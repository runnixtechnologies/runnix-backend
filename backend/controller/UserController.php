<?php
namespace Controller;

use Model\User;
use Model\Otp;
use Config\JwtHandler;
use Model\Store;

// Manually include AutoNotificationService if autoloader doesn't work
if (!class_exists('Service\\AutoNotificationService')) {
    require_once __DIR__ . '/../service/AutoNotificationService.php';
}
use Service\AutoNotificationService;

class UserController
{

    private $userModel;
    private $otpModel;
    private $storeModel;
    private $autoNotificationService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->otpModel = new Otp();
        $this->storeModel = new Store();
        
        // Try to load AutoNotificationService, handle gracefully if not available
        try {
            $this->autoNotificationService = new AutoNotificationService();
        } catch (Exception $e) {
            error_log("AutoNotificationService not available: " . $e->getMessage());
            $this->autoNotificationService = null;
        }
    }
    
    
  public function login($data)
{
    $identifier = trim($data['identifier'] ?? '');
    $password   = $data['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        http_response_code(400);
        return ["status" => "error", "message" => "Email/Phone and password are required"];
    }

    // Check if it's a phone number (digits only after removing non-digit characters)
    $rawPhone = preg_replace('/\D/', '', $identifier);

    if (is_numeric($rawPhone)) {
        if (strlen($rawPhone) === 11 && substr($rawPhone, 0, 1) === '0') {
            // 11-digit local format (e.g., 0803...)
            $identifier = '234' . substr($rawPhone, 1); // convert to international format
        } elseif (strlen($rawPhone) === 10) {
            // 10-digit without leading zero (e.g., 803...)
            $identifier = '234' . $rawPhone;
        } elseif (strlen($rawPhone) === 13 && substr($rawPhone, 0, 3) === '234') {
            // already in international format (e.g., 234803...)
            $identifier = $rawPhone;
        } else {
            // Might be an email or invalid number
            if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                return ["status" => "error", "message" => "Invalid phone number format"];
            }
        }
    }

    $user = $this->userModel->login($identifier, $password);

    if (!$user) {
        http_response_code(401);
        return ["status" => "error", "message" => "Invalid credentials"];
    }

    unset($user['password']);

    // Attach store details for merchants
    if ($user['role'] === 'merchant') {
        $storeDetails = $this->userModel->getMerchantStore($user['id']);
        $store = $this->storeModel->getStoreByUserId($user['id']);

        if ($storeDetails && $store) {
            $user['store_id']         = $storeDetails['store_id'];
            $user['store_name']       = $storeDetails['store_name'];
            $user['store_type_id']    = $storeDetails['store_type_id'];
            $user['store_type_name']  = $storeDetails['store_type_name'];
            $user['store_type_image'] = $storeDetails['store_type_image'];
             $user['biz_logo'] = $storeDetails['biz_logo'];
            $user['store_setup']      = true;
        } else {
            $user['store_setup'] = false;
        }
    }

    $jwt = new JwtHandler();
    $payload = [
        "user_id" => $user['id'],
        "role"    => $user['role'],
        "store_id" => $user['store_id'] ?? null,
        "store_type_id" => $user['store_type_id'] ?? null
    ];

    $token = $jwt->encode($payload);

    // Record initial user activity
    $userActivity = new \Model\UserActivity();
    $deviceInfo = json_encode([
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'platform' => 'web'
    ]);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userActivity->recordActivity($user['id'], $user['role'], $deviceInfo, $ipAddress);

    http_response_code(200);
    return [
        "status"  => "success",
        "message" => "Login successful",
        "token"   => $token,
        "user"    => $user
    ];
}


public function setupUserRider($data)
{
    $email = isset($data['email']) ? strtolower(trim($data['email'])) : null;
    $phone = isset($data['phone']) ? '234' . ltrim($data['phone'], '0') : null;
    $password = $data['password'];
    $role = $data['role'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];

    // Check that either phone or email is provided
    if (!$phone && !$email) {
        http_response_code(400);
        return ["status" => "error", "message" => "Either phone or email must be provided"];
    }

    // Validate method
    //$signupMethod = $phone ? 'phone' : 'email';

    $signupMethod = isset($data['signup_method']) ? strtolower($data['signup_method']) : ($phone ? 'phone' : 'email');

    // Check for existing user
    if ($phone && $this->userModel->getUserByPhone($phone)) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this phone already exists"];
    }

    if ($email && $this->userModel->getUserByEmail($email)) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this email already exists"];
    }

    // OTP verification
    if ($signupMethod === 'phone' && !$this->otpModel->isOtpVerified($phone, 'signup')) {
        http_response_code(400);
        return ["status" => "error", "message" => "Phone OTP not verified. Please verify OTP."];
    }

    if ($signupMethod === 'email' && !$this->otpModel->isOtpVerified($email, 'signup')) {
        http_response_code(400);
        return ["status" => "error", "message" => "Email OTP not verified. Please verify OTP."];
    }

    // Referral logic
    $referrer_id = null;
    if (!empty($data['referral_code'])) {
        $referrer = $this->userModel->getUserByReferralCode($data['referral_code']);
        if (!$referrer) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid referral code"];
        }
        $referrer_id = $referrer['id'];
    }

    // Create user
    $userId = $this->userModel->createUser($email, $phone, $password, $role, $referrer_id);
    if ($userId) {
        $profileCreated = $this->userModel->createUserProfile($userId, $first_name, $last_name);
        if ($profileCreated) {
            return [
                'status' => 'success',
                'message' => 'User setup completed successfully',
                'user_id' => $userId
            ];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to create user profile'];
        }
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to create user'];
    }
}

public function setupMerchant($data)
{
    // Validate that user_id is present
    if (empty($data['user_id'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "User ID is required"];
    }

    $userModel = new User();
    $storeModel = new Store();
    $userId = $data['user_id'];

    // Validate that user exists and has merchant role
    $user = $userModel->getUserById($userId);
    if (!$user) {
        http_response_code(404);
        return ["status" => "error", "message" => "User not found"];
    }
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        return ["status" => "error", "message" => "Only merchants can proceed to store setup"];
    }

    // Validate store fields
    $requiredFields = ['store_name', 'biz_address', 'biz_email', 'biz_phone', 'biz_reg_number', 'store_type_id'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            return ["status" => "error", "message" => "$field is required"];
        }
    }

    // Check if store info is unique
    if ($storeModel->storeExists('store_name', $data['store_name']) ||
        $storeModel->storeExists('biz_email', $data['biz_email']) ||
        $storeModel->storeExists('biz_phone', $data['biz_phone']) ||
        $storeModel->storeExists('biz_reg_number', $data['biz_reg_number'])) {
        http_response_code(409);
        return ["status" => "error", "message" => "Business details already exist"];
    }

    // Handle biz logo (optional)
$uploadDir = __DIR__ . '/../../uploads/logos/';
$allowedTypes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/pjpeg',    // common from iOS/Flutter
    'image/x-png'     // sometimes seen from Android
];
$maxSize = 150 * 1024; // 150KB
$filename = null;

if (isset($_FILES['biz_logo']) && $_FILES['biz_logo']['error'] === UPLOAD_ERR_OK) {
    $logo = $_FILES['biz_logo'];

    // Use mime_content_type to get the actual file MIME type
    $detectedMime = mime_content_type($logo['tmp_name']);
    error_log('Uploaded file MIME type: ' . $detectedMime); // optional: helps in debugging

    if (!in_array($detectedMime, $allowedTypes)) {
        http_response_code(400);
        return ["status" => "error", "message" => "Invalid logo format: $detectedMime"];
    }

    if ($logo['size'] > $maxSize) {
        http_response_code(400);
        return ["status" => "error", "message" => "Logo size exceeds 150KB"];
    }

    $safeName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($logo['name']));
    $filename = uniqid('logo_') . '_' . $safeName;
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($logo['tmp_name'], $targetPath)) {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to upload logo"];
    }
    $filename = 'https://api.runnix.africa/uploads/logos/' . $filename;

}
    // Create store
    $storeCreated = $storeModel->createStore(
        $userId,
        $data['store_name'],
        $data['biz_address'],
        $data['biz_email'],
        $data['biz_phone'],
        $data['biz_reg_number'],
        $data['store_type_id'],
        $filename
    );

    if (!$storeCreated) {
        if ($filename && file_exists($targetPath)) {
            unlink($targetPath);
        }
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to create store"];
    }

    http_response_code(201);
    return [
        "status" => "success",
        "message" => "Merchant setup completed",
        "user_id" => $userId
    ];
}


    public function googlePrefill($data)
{
    // Assuming $this->userModel is already injected or instantiated in the class
    if (empty($data['email'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Email is required"];
    }

    // Check if the user already exists
    $existingUser = $this->userModel->getUserByEmail($data['email']);
    if ($existingUser) {
        http_response_code(409);
        return [
            "status" => "exists",
            "message" => "User already exists",
            "user" => $existingUser
        ];
    }

    // Return prefill data if no existing user
    http_response_code(200);
    return [
        "status" => "prefill",
        "message" => "Prefill registration form",
        "data" => [
            "first_name" => $data['first_name'] ?? "",  // Use empty string as default if not provided
            "last_name" => $data['last_name'] ?? "",    // Same for last_name
            "email" => $data['email']                   // Email is mandatory and should be in data
        ]
    ];
}

    public function verifyPhoneOtp(string $phone, string $otp)
{
    $otpModel = new Otp();
    $isVerified = $otpModel->verifyOtp($phone, $otp, 'signup');

    if (!$isVerified) {
        http_response_code(401);
        return ["status" => "error", "message" => "Invalid OTP for phone"];
    }

    http_response_code(200);
    return ["status" => "success", "message" => "Phone OTP verified"];
}

public function verifyEmailOtp(string $email, string $otp)
{
    $otpModel = new Otp();
    $isVerified = $otpModel->verifyOtp($email, $otp, 'signup');

    if (!$isVerified) {
        http_response_code(401);
        return ["status" => "error", "message" => "Invalid OTP for email"];
    }

    http_response_code(200);
    return ["status" => "success", "message" => "Email OTP verified"];
}


 public function finalizeSignup($data)
{
    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            return ["status" => "error", "message" => "$field is required"];
        }
    }

    if ($data['password'] !== $data['confirm_password']) {
        http_response_code(400);
        return ["status" => "error", "message" => "Passwords do not match"];
    }

    $signupMethod = strtolower($data['signup_method'] ?? 'phone');
    $phone = '234' . ltrim($data['phone'], '0');

    $userModel = new User();

    // Check if user already exists by phone or email
    if ($userModel->getUserByPhone($phone)) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this phone already exists"];
    }

    if ($userModel->getUserByEmail($data['email'])) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this email already exists"];
    }

    // OTP check if the signup method is phone
    if ($signupMethod === 'phone') {
        $otpModel = new Otp();
        if (!$otpModel->isOtpVerified($phone, 'signup')) {
            http_response_code(401);
            return ["status" => "error", "message" => "OTP not verified. Please verify OTP before signing up."];
        }
    }

    // Referral logic
    $referrer_id = null;
    if (!empty($data['referral_code'])) {
        $referrer = $userModel->getUserByReferralCode($data['referral_code']);
        if (!$referrer) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid referral code"];
        }
        $referrer_id = $referrer['id'];
    }

    $google_id = $data['google_id'] ?? null;
    // Create the user
    $userId = $userModel->createUser(
        $data['email'],
        $phone,
        $data['password'],
        $data['role'],
        $referrer_id,
        $google_id
    );

    if (!$userId) {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to create account"];
    }

    // Create user profile
    $profileCreated = $userModel->createUserProfile(
        $userId,
        $data['first_name'],
        $data['last_name']
    );

    if (!$profileCreated) {
        // If profile creation fails, rollback user creation
        $userModel->deleteUser($userId);
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to create user profile"];
    }

    // Merchant-specific store creation logic
    if (strtolower($data['role']) === 'merchant') {
        $storeName = $data['store_name'] ?? null;
        $storeAddress = $data['biz_address'] ?? null;
        $bizEmail = $data['biz_email'] ?? null;
        $bizPhone = $data['biz_phone'] ?? null;
        $bizRegNo = $data['biz_reg_number'] ?? null;
        $storeType = $data['store_type'] ?? null;

        // Validate business fields
        if (!$storeName || !$storeAddress || !$bizEmail || !$bizPhone || !$bizRegNo || !$storeType) {
            http_response_code(400);
            return ["status" => "error", "message" => "All business fields are required for store owners"];
        }

        // Check if store already exists
        $storeModel = new Store();
        if ($storeModel->storeExists('store_name', $storeName)) {
            http_response_code(409);
            return ["status" => "error", "message" => "Business name already exists"];
        }
        if ($storeModel->storeExists('biz_email', $bizEmail)) {
            http_response_code(409);
            return ["status" => "error", "message" => "Business email already exists"];
        }
        if ($storeModel->storeExists('biz_phone', $bizPhone)) {
            http_response_code(409);
            return ["status" => "error", "message" => "Business phone already exists"];
        }
        if ($storeModel->storeExists('biz_reg_number', $bizRegNo)) {
            http_response_code(409);
            return ["status" => "error", "message" => "Business registration number already exists"];
        }

        // Handle logo upload
        $uploadDir = __DIR__ . '/../../uploads/logos/';
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 150 * 1024; // 150KB
        $filename = null;
        $targetPath = null;

        if (isset($_FILES['biz_logo']) && $_FILES['biz_logo']['error'] === UPLOAD_ERR_OK) {
            $logo = $_FILES['biz_logo'];
            $fileType = $logo['type'];

            // Validate logo file type
            if (!in_array($fileType, $allowedTypes)) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Invalid file type. Only JPG, JPEG, and PNG files are allowed.'
                ];
            }

            // Validate logo file size
            if ($logo['size'] > $maxSize) {
                http_response_code(400);
                return [
                    "status" => "error",
                    "message" => "File size exceeds limit. Maximum allowed size is 150KB."
                ];
            }

            $safeName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($logo['name']));
            $filename = uniqid('logo_') . '_' . $safeName;
            $targetPath = $uploadDir . $filename;

            // Upload the logo file
            if (!move_uploaded_file($logo['tmp_name'], $targetPath)) {
                // Rollback user profile and user data deletion on logo upload failure
               
                $userModel->deleteUser($userId);
                http_response_code(500);
                return ["status" => "error", "message" => "Failed to upload logo"];
            }
        }

        // Create store
        $storeCreated = $storeModel->createStore(
            $userId,
            $storeName,
            $storeAddress,
            $bizEmail,
            $bizPhone,
            $bizRegNo,
            $storeType,
            $filename
        );

        // If store creation fails, delete the uploaded logo file and rollback user/profile creation
        if (!$storeCreated) {
            if ($filename && file_exists($targetPath)) {
                unlink($targetPath); // Delete the uploaded logo
            }
            
            $userModel->deleteUser($userId); // Automatically handles profile and user deletion in a transaction.

            http_response_code(500);
            return ["status" => "error", "message" => "Failed to create store"];
        }
    }

    // If everything was successful, return success
    http_response_code(201);
    return [
        "status" => "success",
        "message" => "Account created successfully",
        "user_id" => $userId
    ];
}

    public function selectRole($data)
    {
        if (empty($data['role'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Role is required"];
        }

        if (!in_array($data['role'], ['user', 'merchant', 'rider'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid role"];
        }

        http_response_code(200);
        return ["status" => "success", "message" => "Role selected", "role" => $data['role']];
    }

    public function collectPersonalDetails($data)
    {
        $required = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return ["status" => "error", "message" => "$field is required"];
            }
        }

        if ($data['password'] !== $data['confirm_password']) {
            http_response_code(400);
            return ["status" => "error", "message" => "Passwords do not match"];
        }

        
        http_response_code(200);
        return [
            "status" => "success",
            "message" => "Personal details collected"
            //"data" => $data
        ];
    }

    public function deleteUser($userId)
{
    $userModel = new User();
    $storeModel = new Store();

    // Fetch user to get role and validate existence
    $user = $userModel->getUserById($userId);
    if (!$user) {
        http_response_code(404);
        return ["status" => "error", "message" => "User not found"];
    }

    // Delete store and logo if user is a merchant
    if (strtolower($user['role']) === 'merchant') {
        $store = $storeModel->getStoreByUserId($userId);
        if ($store) {
            // Remove logo file if it exists
            $logoPath = __DIR__ . '/../../uploads/logos/' . $store['biz_logo'];
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
            $storeModel->deleteStoreByUserId($userId);
        }
    }

    // Delete user profile
    $userModel->deleteUserProfile($userId);

    // Finally, delete user account
    $userModel->deleteUser($userId);

    http_response_code(200);
    return ["status" => "success", "message" => "User deleted successfully"];
}

// Soft delete user account with confirmation
public function softDeleteAccount($data, $user)
{
    $userModel = new User();
    
    $userId = $user['user_id'];
    $reason = $data['reason'] ?? null; // Reason is now optional
    
    // Check if user exists and is not already soft deleted
    $existingUser = $userModel->getUserById($userId);
    if (!$existingUser) {
        http_response_code(404);
        return ["status" => "error", "message" => "User not found or account already deleted"];
    }
    
    // Check if user is already soft deleted
    if ($userModel->isUserSoftDeleted($userId)) {
        http_response_code(400);
        return ["status" => "error", "message" => "Account is already deleted"];
    }
    
    // Perform soft delete
    $success = $userModel->softDeleteUser($userId, $userId, $reason, 'self');
    
    if ($success) {
        // Log the deletion for audit purposes
        $reasonText = $reason ? $reason : 'No reason provided';
        error_log("User account soft deleted - User ID: {$userId}, Reason: {$reasonText}, Deleted at: " . date('Y-m-d H:i:s'));
        
        http_response_code(200);
        return [
            "status" => "success", 
            "message" => "Your account has been successfully deleted. You can reactivate it within 60 days by contacting support.",
            "reactivation_deadline" => date('Y-m-d H:i:s', strtotime('+60 days')),
            "can_reactivate" => true
        ];
    } else {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to delete account. Please try again or contact support."];
    }
}

// Reactivate user account (for support/admin use)
public function reactivateAccount($userId, $adminUser = null)
{
    $userModel = new User();
    
    // Check if user exists and is soft deleted
    if (!$userModel->isUserSoftDeleted($userId)) {
        http_response_code(400);
        return ["status" => "error", "message" => "Account is not deleted or does not exist"];
    }
    
    // Perform reactivation
    $success = $userModel->reactivateUser($userId);
    
    if ($success) {
        // Log the reactivation for audit purposes
        $adminId = $adminUser ? $adminUser['user_id'] : 'system';
        error_log("User account reactivated - User ID: {$userId}, Reactivated by: {$adminId}, Reactivated at: " . date('Y-m-d H:i:s'));
        
        http_response_code(200);
        return [
            "status" => "success", 
            "message" => "Account has been successfully reactivated"
        ];
    } else {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to reactivate account. Please contact support."];
    }
}

public function collectStoreDetails($data)
{
    // Validate required fields
   
    $requiredFields = ['store_name', 'biz_email', 'biz_address', 'biz_phone', 'biz_reg_number'];


    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            return ["status" => "error", "message" => "$field is required"];
        }
    }

   
    

    // Validate biz_phone (must be unique, 10 or 11 digits)
    // Remove leading zero if present, prepend 234
    $originalPhone = $data['biz_phone'];
    if (!preg_match('/^\d{10,11}$/', $originalPhone)) {
        http_response_code(400);
        return ["status" => "error", "message" => "Business phone number must be 10 or 11 digits"];
    }
    $bizPhone = preg_replace('/^0/', '234', $originalPhone);
    
    // Create store
    $storeCreated = $storeModel->createStore(
        $userId,
        $data['store_name'],
        $data['biz_address'],
        $data['biz_email'],
        $bizPhone,
        $data['biz_reg_number'],
        $data['store_type_id'],
        $filename
    );

    if (!$storeCreated) {
        if ($filename && file_exists($targetPath)) {
            unlink($targetPath);
        }
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to create store"];
    }

    http_response_code(201);
    return [
        "status" => "success",
        "message" => "Merchant setup completed",
        "user_id" => $userId
    ];
}

/**
 * Verify merchant account (admin function)
 */
public function verifyMerchantAccount($data, $user)
{
    try {
        // Check if user is admin
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Only admins can verify merchant accounts'];
        }

        // Validate required fields
        if (!isset($data['merchant_id']) || !isset($data['verification_status'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'merchant_id and verification_status are required'];
        }

        $merchantId = $data['merchant_id'];
        $verificationStatus = $data['verification_status'];
        $adminNotes = $data['admin_notes'] ?? null;

        // Update merchant verification status in database
        $updateResult = $this->userModel->updateUserVerificationStatus($merchantId, $verificationStatus, $adminNotes);

        if (!$updateResult) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update verification status'];
        }

        // Send automatic notification (if service is available)
        $notificationResult = null;
        if ($this->autoNotificationService !== null) {
            $notificationResult = $this->autoNotificationService->notifyAccountVerification(
                $merchantId,
                $verificationStatus,
                $adminNotes
            );
        }

        // Log notification result
        if ($notificationResult !== null && $notificationResult['status'] === 'success') {
            error_log("Account verification notification sent successfully for merchant: $merchantId");
        } else if ($notificationResult !== null) {
            error_log("Failed to send account verification notification: " . $notificationResult['message']);
        } else {
            error_log("AutoNotificationService not available - notification skipped for merchant: $merchantId");
        }

        http_response_code(200);
        return [
            'status' => 'success',
            'message' => 'Merchant account verification updated successfully',
            'verification_status' => $verificationStatus,
            'notification_sent' => $notificationResult['status'] === 'success'
        ];

    } catch (\Exception $e) {
        error_log("Verify merchant account error: " . $e->getMessage());
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Internal server error'];
    }
}

/**
 * Test notification endpoint for Postman testing
 */
public function testNotification($data, $user)
{
    try {
        // Validate required fields
        if (!isset($data['merchant_id']) || !isset($data['notification_type'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'merchant_id and notification_type are required'];
        }

        $merchantId = $data['merchant_id'];
        $notificationType = $data['notification_type'];

        $result = [];

        switch ($notificationType) {
            case 'new_order':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyNewOrder(
                        $data['order_id'] ?? 123,
                        $merchantId,
                        $data['order_number'] ?? 'ORD-TEST-001',
                        $data['customer_name'] ?? 'Test Customer',
                        $data['customer_phone'] ?? '08012345678',
                        $data['order_total'] ?? '2500',
                        $data['delivery_address'] ?? '123 Test Street, Lagos',
                        $data['items_count'] ?? 3
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            case 'payment_received':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyPaymentProcessed(
                        $data['payment_id'] ?? 456,
                        $merchantId,
                        $data['amount'] ?? '2500',
                        $data['payment_method'] ?? 'Card',
                        $data['transaction_id'] ?? 'TXN-TEST-789',
                        $data['order_number'] ?? 'ORD-TEST-001',
                        'received'
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            case 'account_verification':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyAccountVerification(
                        $merchantId,
                        $data['verification_status'] ?? 'approved',
                        $data['admin_notes'] ?? 'Test verification'
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            case 'customer_review':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyCustomerReview(
                        $data['review_id'] ?? 789,
                        $merchantId,
                        $data['customer_name'] ?? 'Test Customer',
                        $data['rating'] ?? 5,
                        $data['review_text'] ?? 'Great food and fast delivery!',
                        $data['order_number'] ?? 'ORD-TEST-001'
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            case 'customer_message':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyCustomerMessage(
                        $data['message_id'] ?? 101,
                        $merchantId,
                        $data['customer_name'] ?? 'Test Customer',
                        $data['message_text'] ?? 'Can you make the food less spicy?',
                        $data['order_id'] ?? 123
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            case 'order_status_update':
                if ($this->autoNotificationService !== null) {
                    $result = $this->autoNotificationService->notifyOrderStatusChange(
                        $data['order_id'] ?? 123,
                        $data['status'] ?? 'confirmed',
                        $merchantId,
                        $data['order_number'] ?? 'ORD-TEST-001',
                        $data['customer_name'] ?? 'Test Customer'
                    );
                } else {
                    $result = ['status' => 'error', 'message' => 'AutoNotificationService not available'];
                }
                break;

            default:
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Invalid notification type'];
        }

        http_response_code(200);
        return [
            'status' => 'success',
            'message' => 'Test notification sent successfully',
            'notification_type' => $notificationType,
            'merchant_id' => $merchantId,
            'result' => $result
        ];

    } catch (\Exception $e) {
        error_log("Test notification error: " . $e->getMessage());
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Internal server error'];
    }
}

    public function resetPassword($phone, $newPassword)
    {
    
        // Check if OTP was verified for this phone and purpose
        if (!$this->otpModel->OtpVerified($phone, 'password_reset')) {
    http_response_code(401);
    return ["status" => "error", "message" => "OTP not verified for this phone"];
        }

        // Call model method to update the password
        $result =$this->userModel->resetPasswordByPhone($phone, $newPassword);

        if ($result) {
            return ["status" => "success", "message" => "Password reset successful"];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to reset password"];
        }
    }

    public function setUserStatus($data, $user) {
    if (!isset($data['is_online'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "is_online is required."];
    }

    // Check if user is rider
    if ($user['role'] !== 'rider') {
        http_response_code(403);
        return ["status" => "error", "message" => "Only riders can change status."];
    }

    return $this->userModel->updateUserStatus($user['user_id'], $user['role'], $data['is_online']);
}

public function getStatus($user) {
    if (!isset($user['user_id'])) {
        http_response_code(401);
        return ["status" => "error", "message" => "Unauthorized"];
    }

    $userId = $user['user_id'];
    return $this->userModel->getUserStatus($userId);
}

    // Profile Management Methods
    public function getProfile($user)
    {
        $userId = $user['user_id'];
        
        try {
            // Get user basic info
            $userData = $this->userModel->getUserById($userId);
            if (!$userData) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'User not found'];
            }
            
            // Get user profile details
            $profileData = $this->userModel->getUserProfile($userId);
            if (!$profileData) {
                // Create default profile if it doesn't exist
                $profileData = [
                    'first_name' => '',
                    'last_name' => '',
                    'address' => '',
                    'profile_picture' => null
                ];
            }
            
            // Combine user and profile data to match UI design
            $fullProfile = [
                'user_id' => $userId,
                'role' => $userData['role'],
                'first_name' => $profileData['first_name'] ?? '',
                'last_name' => $profileData['last_name'] ?? '',
                'phone' => $userData['phone'],
                'email' => $userData['email'],
                'address' => $profileData['address'] ?? '',
                'profile_picture' => $profileData['profile_picture'] ?? null,
                'support_pin' => $userData['support_pin'] ?? null,
                'is_verified' => (bool)$userData['is_verified'],
                'status' => $userData['status'],
                'created_at' => $userData['created_at'],
                'updated_at' => $userData['updated_at'] ?? null
            ];
            
            // If user is a merchant, add business information
            if ($userData['role'] === 'merchant') {
                $storeData = $this->storeModel->getStoreByUserId($userId);
                if ($storeData) {
                    // Add business information
                    $fullProfile['business'] = [
                        'store_name' => $storeData['store_name'] ?? '',
                        'business_address' => $storeData['biz_address'] ?? '',
                        'business_email' => $storeData['biz_email'] ?? '',
                        'business_phone' => $storeData['biz_phone'] ?? '',
                        'business_registration_number' => $storeData['biz_reg_number'] ?? '',
                        'business_logo' => $storeData['biz_logo'] ?? null,
                        'business_url' => $storeData['biz_url'] ?? null, // Will be null if field doesn't exist
                        'store_id' => $storeData['id'] ?? null,
                        'store_type_id' => $storeData['store_type_id'] ?? null
                    ];
                } else {
                    // Merchant but no store setup
                    $fullProfile['business'] = null;
                }
            }
            
            http_response_code(200);
            return [
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'data' => $fullProfile
            ];
            
        } catch (Exception $e) {
            error_log("getProfile error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to retrieve profile'];
        }
    }
    
    public function updateProfile($data, $user)
    {
        $userId = $user['user_id'];
        
        try {
        // Validate required fields
            $required = ['first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
            http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
        }
        }
        
            // Email and phone updates are not allowed for security reasons
            // Users must contact support to update these sensitive fields
            if (!empty($data['email'])) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Email updates are not allowed. Please contact support for email changes.'];
            }
            
            if (!empty($data['phone'])) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Phone number updates are not allowed. Please contact support for phone number changes.'];
            }
            
            // Begin transaction
            $this->userModel->beginTransaction();
            
            try {
                // Update or create user profile (only first_name and last_name allowed)
        $profileData = [
            'user_id' => $userId,
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name'])
                ];
                
                $profileUpdated = $this->userModel->updateUserProfile($profileData);
                if (!$profileUpdated) {
                    // If profile doesn't exist, create it
                    $profileCreated = $this->userModel->createUserProfile(
                        $userId,
                        $profileData['first_name'],
                        $profileData['last_name']
                    );
                    if (!$profileCreated) {
                        throw new Exception('Failed to create user profile');
                    }
                }
                
                // Commit transaction
                $this->userModel->commit();
                
                // Get updated profile
                $updatedProfile = $this->getProfile($user);
                
            http_response_code(200);
                return [
                    'status' => 'success',
                    'message' => 'Profile updated successfully',
                    'data' => $updatedProfile['data']
                ];
                
            } catch (Exception $e) {
                // Rollback transaction
                $this->userModel->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("updateProfile error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }
    
    public function updateProfilePicture($user)
    {
        $userId = $user['user_id'];
        
        // Handle profile picture upload
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Profile picture is required'];
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileName = $_FILES['profile_picture']['name'];

        // Enhanced debugging for image format issues
        error_log("=== PROFILE_PICTURE UPLOAD DEBUG ===");
        error_log("File name: " . $fileName);
        error_log("File type (MIME): " . $fileType);
        error_log("File size: " . $fileSize . " bytes");
        error_log("Allowed types: " . implode(', ', $allowedTypes));
        error_log("Is file type allowed: " . (in_array($fileType, $allowedTypes) ? 'YES' : 'NO'));
        
        if (!in_array($fileType, $allowedTypes)) {
            error_log("=== UNSUPPORTED IMAGE FORMAT ERROR (PROFILE_PICTURE) ===");
            error_log("Received MIME type: " . $fileType);
            error_log("Expected MIME types: " . implode(', ', $allowedTypes));
            error_log("File name: " . $fileName);
            
            // Try to detect MIME type from file extension as fallback
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            error_log("File extension: " . $fileExtension);
            
            $extensionToMime = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png'
            ];
            
            if (isset($extensionToMime[$fileExtension])) {
                $detectedMime = $extensionToMime[$fileExtension];
                error_log("Detected MIME type from extension: " . $detectedMime);
                
                if (in_array($detectedMime, $allowedTypes)) {
                    error_log("Using detected MIME type instead of reported type");
                    $fileType = $detectedMime;
                } else {
                    http_response_code(415);
                    return ['status' => 'error', 'message' => 'Unsupported image format. Received: ' . $fileType . ', File: ' . $fileName];
                }
            } else {
                http_response_code(415);
                return ['status' => 'error', 'message' => 'Unsupported image format. Received: ' . $fileType . ', File: ' . $fileName];
            }
        }
        
        if ($fileSize > 2 * 1024 * 1024) { // 2MB
            http_response_code(413);
            return ['status' => 'error', 'message' => 'Image exceeds max size of 2MB'];
        }
        
        $uploadDir = __DIR__ . '/../../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to upload profile picture'];
        }
        
        $profilePictureUrl = 'https://api.runnix.africa/uploads/profiles/' . $filename;
        
        // Update profile picture in database
        $result = $this->userModel->updateProfilePicture($userId, $profilePictureUrl);
        
        if ($result) {
            http_response_code(200);
            return [
                'status' => 'success', 
                'message' => 'Profile picture updated successfully',
                'data' => ['profile_picture' => $profilePictureUrl]
            ];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update profile picture'];
        }
    }
    
    public function changePhoneNumber($data, $user)
    {
        $userId = $user['user_id'];
        
        if (empty($data['new_phone'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New phone number is required'];
        }
        
        // Format phone number
        $newPhone = '234' . ltrim($data['new_phone'], '0');
        
        // Check if phone is already taken by another user
        $existingUser = $this->userModel->getUserByPhone($newPhone);
        if ($existingUser && $existingUser['id'] != $userId) {
            http_response_code(409);
            return ['status' => 'error', 'message' => 'Phone number is already taken by another user'];
        }
        
        // Generate and send OTP
        $otp = $this->otpModel->generateOtp($newPhone, 'phone_change');
        
        if ($otp) {
            // Store the new phone number temporarily (you might want to create a temporary table for this)
            // For now, we'll use the OTP table to store the pending phone change
            
            http_response_code(200);
            return [
                'status' => 'success', 
                'message' => 'OTP sent to new phone number',
                'data' => ['phone' => $newPhone]
            ];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send OTP'];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        // Validate required fields
        if (empty($currentPassword)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Current password is required'];
        }
        
        if (empty($newPassword)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New password is required'];
        }
        
        // Validate password strength
        $passwordValidation = $this->validatePasswordStrength($newPassword);
        if (!$passwordValidation['valid']) {
            http_response_code(400);
            return ['status' => 'error', 'message' => $passwordValidation['message']];
        }
        
        // Get current user data
        $userData = $this->userModel->getUserById($userId);
        if (!$userData) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $userData['password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Current password is incorrect'];
        }
        
        // Check if new password is same as current password
        if (password_verify($newPassword, $userData['password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New password must be different from current password'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $result = $this->userModel->updatePassword($userId, $hashedPassword);
        
        if ($result) {
            // Log password change activity
            $this->logPasswordChange($userId, $userData['email'] ?? $userData['phone']);
            
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Password changed successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to change password'];
        }
    }

    /**
     * Validate password strength
     */
    private function validatePasswordStrength($password) {
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

    /**
     * Log password change activity
     */
    private function logPasswordChange($userId, $identifier) {
        try {
            $logData = [
                'user_id' => $userId,
                'action' => 'password_change',
                'identifier' => $identifier,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("Password change activity: " . json_encode($logData));
            
        } catch (Exception $e) {
            error_log("Failed to log password change: " . $e->getMessage());
        }
    }

    public function deleteAccount($user)
    {
        $userId = $user['user_id'];
        $userRole = $user['role'];
        
        // Get user data for cleanup
        $userData = $this->userModel->getUserById($userId);
        if (!$userData) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        try {
            // Begin transaction for data cleanup
            $this->userModel->beginTransaction();
            
            // Delete store data if user is a merchant
            if ($userRole === 'merchant') {
                $store = $this->storeModel->getStoreByUserId($userId);
                if ($store) {
                    // Delete store logo file if exists
                    if (!empty($store['biz_logo'])) {
                        $logoPath = __DIR__ . '/../../uploads/logos/' . $store['biz_logo'];
                        if (file_exists($logoPath)) {
                            unlink($logoPath);
                        }
                    }
                    
                    // Delete store
                    $this->storeModel->deleteStoreByUserId($userId);
                }
            }
            
            // Delete profile picture if exists
            $profileData = $this->userModel->getUserProfile($userId);
            if ($profileData && !empty($profileData['profile_picture'])) {
                $profilePath = __DIR__ . '/../../uploads/profiles/' . basename($profileData['profile_picture']);
                if (file_exists($profilePath)) {
                    unlink($profilePath);
                }
            }
            
            // Delete user profile
            $this->userModel->deleteUserProfile($userId);
            
            // Delete user account
            $this->userModel->deleteUser($userId);
            
            // Commit transaction
            $this->userModel->commit();
            
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Account deleted successfully'];
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->userModel->rollback();
            error_log("deleteAccount error: " . $e->getMessage());
            
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete account'];
        }
    }


}
