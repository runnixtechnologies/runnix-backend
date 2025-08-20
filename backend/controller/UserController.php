<?php
namespace Controller;

use Model\User;
use Model\Otp;
use Config\JwtHandler;
use Model\Store;

class UserController
{

    private $userModel;
    private $otpModel;
    private $storeModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->otpModel = new Otp();
        $this->storeModel = new Store();  
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
    

    

    // Validate biz_logo (must be jpg/png and <= 150KB)
    if (!empty($data['biz_logo'])) {
        $logo = $data['biz_logo'];
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        //$fileExtension = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        $fileExtension = strtolower(pathinfo($logo, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Logo must be a JPG or PNG file"];
        }

      
    }

    // Return collected data with transformation (phone number)
    $responseData = [
        "store_name" => $data['store_name'],
        "biz_email" => $data['biz_email'],
        "biz_address" => $data['biz_address'],
        "biz_phone" => $bizPhone,
        "biz_reg_number" => $data['biz_reg_number']
    ];
    

    if (!empty($data['biz_logo'])) {
        $responseData['biz_logo'] = $data['biz_logo']; // Assuming it's a file object, or handle the upload
    }

    http_response_code(200);
    return [
        "status" => "success",
        "message" => "Store details collected successfully",
        "data" => $responseData
    ];
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
        
        $userData = $this->userModel->getUserById($userId);
        if (!$userData) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $profileData = $this->userModel->getUserProfile($userId);
        
        // Combine user and profile data
        $profile = [
            'name' => $profileData['first_name'] . ' ' . $profileData['last_name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'address' => $profileData['address'] ?? '',
            'profile_picture' => $profileData['profile_picture'] ?? null
        ];
        
        http_response_code(200);
        return [
            'status' => 'success',
            'data' => $profile
        ];
    }
    
    public function updateProfile($data, $user)
    {
        $userId = $user['user_id'];
        
        // Validate required fields
        if (empty($data['name'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Name is required'];
        }
        
        if (empty($data['email'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Email is required'];
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid email format'];
        }
        
        // Check if email is already taken by another user
        $existingUser = $this->userModel->getUserByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $userId) {
            http_response_code(409);
            return ['status' => 'error', 'message' => 'Email is already taken by another user'];
        }
        
        // Split name into first and last name
        $nameParts = explode(' ', trim($data['name']), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Update user email
        $userUpdateResult = $this->userModel->updateUserEmail($userId, $data['email']);
        
        // Update profile
        $profileData = [
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address' => $data['address'] ?? ''
        ];
        
        $profileUpdateResult = $this->userModel->updateUserProfile($profileData);
        
        if ($userUpdateResult && $profileUpdateResult) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Profile updated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update profile'];
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
        
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(415);
            return ['status' => 'error', 'message' => 'Unsupported image format. Use JPEG or PNG'];
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
    
    public function changePassword($data, $user)
    {
        $userId = $user['user_id'];
        
        // Validate required fields
        if (empty($data['current_password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Current password is required'];
        }
        
        if (empty($data['new_password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New password is required'];
        }
        
        if (empty($data['confirm_password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Confirm password is required'];
        }
        
        // Validate password confirmation
        if ($data['new_password'] !== $data['confirm_password']) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New password and confirm password do not match'];
        }
        
        // Validate password strength
        if (strlen($data['new_password']) < 6) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'New password must be at least 6 characters long'];
        }
        
        // Get current user data
        $userData = $this->userModel->getUserById($userId);
        if (!$userData) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($data['current_password'], $userData['password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        
        // Update password
        $result = $this->userModel->updatePassword($userId, $hashedPassword);
        
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Password changed successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to change password'];
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
