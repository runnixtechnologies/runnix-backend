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
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to create user profile"];
        }

        // Create store if merchant
        if (strtolower($data['role']) === 'merchant') {
            $storeName = $data['store_name'] ?? null;
            $storeAddress = $data['store_address'] ?? null;
            $bizEmail = $data['biz_email'] ?? null;
            $bizPhone = $data['biz_phone'] ?? null;
            $bizRegNo = $data['biz_reg_number'] ?? null;
            $logo = $_FILES['biz_logo'] ?? null;
        
            if (!$storeName || !$storeAddress || !$bizEmail || !$bizPhone || !$bizRegNo) {
                http_response_code(400);
                return ["status" => "error", "message" => "All business fields and logo are required for store owners"];
            }
        
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($logo['type'], $allowedTypes)) {
                http_response_code(400);
                return ["status" => "error", "message" => "Invalid logo format. Only JPG and PNG allowed"];
            }
        
            // Validate file size (≤ 150KB)
            if ($logo['size'] > 150 * 1024) {
                http_response_code(400);
                return ["status" => "error", "message" => "Logo size must be 150KB or less"];
            }
        
            // Check uniqueness of business fields
            $storeModel = new Store();
            if ($storeModel->storeExists('biz_name', $storeName)) {
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
        
            // Optional: Save the logo (handle upload)
            $uploadDir = __DIR__ . '/../../uploads/logos/';
            $filename = uniqid('logo_') . '_' . basename($logo['name']);
            $targetPath = $uploadDir . $filename;
        
            if (!move_uploaded_file($logo['tmp_name'], $targetPath)) {
                http_response_code(500);
                return ["status" => "error", "message" => "Failed to upload logo"];
            }
        
            // Save store
            $storeCreated = $storeModel->createStore(
                $userId,
                $storeName,
                $storeAddress,
                $bizEmail,
                $bizPhone,
                $bizRegNo,
                $filename // store the logo file name or path
            );
        
            if (!$storeCreated) {
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
        $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password'];

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
            "message" => "Personal details collected",
            "data" => $data
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


}
