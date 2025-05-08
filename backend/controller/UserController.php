<?php
namespace Controller;

use Model\User;
use Model\Otp;
use Config\JwtHandler;
use Model\Store;

class UserController
{
    public function login($data)
    {
        $userModel = new User();
        $phone = $data['phone'];
        $password = $data['password'];

        if (empty($phone) || empty($password)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Phone and password are required"];
        }

        $user = $userModel->login($phone, $password);

        if (!$user) {
            http_response_code(401);
            return ["status" => "error", "message" => "Invalid credentials"];
        }

        $jwt = new JwtHandler();
        $payload = ["user_id" => $user['id'], "role" => $user['role']];
        $token = $jwt->encode($payload);

        http_response_code(200);
        return [
            "status" => "success",
            "message" => "Login successful",
            "token" => $token,
            "user" => $user
        ];
    }

    public function googlePrefill($data)
    {
        $userModel = new User();

        if (empty($data['email'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Email is required"];
        }

        $existingUser = $userModel->getUserByEmail($data['email']);
        if ($existingUser) {
            http_response_code(409);
            return ["status" => "exists", "message" => "User already exists", "user" => $existingUser];
        }

        http_response_code(200);
        return [
            "status" => "prefill",
            "message" => "Prefill registration form",
            "data" => [
                "first_name" => $data['first_name'] ?? "",
                "last_name" => $data['last_name'] ?? "",
                "email" => $data['email'] ?? ""
            ]
        ];
    }

    public function verifyOtp($phone, $otp)
    {
        $otpModel = new Otp();
        $isVerified = $otpModel->verifyOtp($phone, $otp, 'signup');

        if (!$isVerified) {
            http_response_code(401);
            return ["status" => "error", "message" => "Invalid OTP"];
        }

        http_response_code(200);
        return ["status" => "success", "message" => "OTP verified"];
    }

   public function finalizeSignup($data)
{
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

    if ($userModel->getUserByPhone($phone)) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this phone already exists"];
    }

    if ($userModel->getUserByEmail($data['email'])) {
        http_response_code(409);
        return ["status" => "error", "message" => "User with this email already exists"];
    }

    if ($signupMethod === 'phone') {
        $otpModel = new Otp();
        if (!$otpModel->isOtpVerified($phone, 'signup')) {
            http_response_code(401);
            return ["status" => "error", "message" => "OTP not verified. Please verify OTP before signing up."];
        }
    }

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

    $profileCreated = $userModel->createUserProfile(
        $userId,
        $data['first_name'],
        $data['last_name']
    );

    if (!$profileCreated) {
        $userModel->deleteUser($userId);
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to create user profile"];
    }

    if (strtolower($data['role']) === 'merchant') {
        $storeName = $data['store_name'] ?? null;
        $storeAddress = $data['biz_address'] ?? null;
        $bizEmail = $data['biz_email'] ?? null;
        $bizPhone = $data['biz_phone'] ?? null;
        $bizRegNo = $data['biz_reg_number'] ?? null;
        $storeType = $data['store_type'] ?? null;

        if (!$storeName || !$storeAddress || !$bizEmail || !$bizPhone || !$bizRegNo || !$storeType) {
            http_response_code(400);
            return ["status" => "error", "message" => "All business fields are required for store owners"];
        }

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

        $uploadDir = __DIR__ . '/../../uploads/logos/';
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 150 * 1024; // 150KB
        $filename = null;

        if (isset($_FILES['biz_logo']) && $_FILES['biz_logo']['error'] === UPLOAD_ERR_OK) {
            $logo = $_FILES['biz_logo'];
            $fileType = $logo['type'];

            if (!in_array($fileType, $allowedTypes)) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Invalid file type. Only JPG, JPEG, and PNG files are allowed.'
                ];
            }

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

            if (!move_uploaded_file($logo['tmp_name'], $targetPath)) {
                $userModel->deleteUserProfile($userId);
                $userModel->deleteUser($userId);
                http_response_code(500);
                return ["status" => "error", "message" => "Failed to upload logo"];
            }
        }

        $storeCreated = $storeModel->createStore(
            $userId,
            $storeName,
            $storeAddress,
            $bizEmail,
            $bizPhone,
            $bizRegNo,
            $filename,
            $storeType
        );

        if (!$storeCreated) {
            if ($filename) {
                unlink($targetPath);
            }
            $userModel->deleteUserProfile($userId);
            $userModel->deleteUser($userId);
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to create store"];
        }
    }

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


}
