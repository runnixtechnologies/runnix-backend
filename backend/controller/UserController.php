<?php
namespace Controller;

use Model\User;
use Model\Otp;
use Config\JwtHandler;

class UserController
{
    /*public function signup($data)
    {
        $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400); // Bad Request
                return ["status" => "error", "message" => "$field is required"];
            }
        }

        if ($data['password'] !== $data['confirm_password']) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Passwords do not match"];
        }
        $phone = '234' . ltrim($data['phone'], '0');
        $userModel = new User();

        if ($userModel->getUserByPhone($data['phone'])) {
            http_response_code(409); // Conflict
            return ["status" => "error", "message" => "User with this phone already exists"];
        }

        if ($userModel->getUserByEmail($data['email'])) {
            http_response_code(409); // Conflict
            return ["status" => "error", "message" => "User with this email already exists"];
        }

        $otpModel = new Otp();
        if (!$otpModel->verifyOtp($data['phone'], null, 'signup', true)) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Phone number is not verified"];
        }

        $referrer_id = null;
        if (!empty($data['referral_code'])) {
            $referrer = $userModel->getUserByReferralCode($data['referral_code']);
            if (!$referrer) {
                http_response_code(400); // Bad Request
                return ["status" => "error", "message" => "Invalid referral code"];
            }
            $referrer_id = $referrer['id'];
        }

        $userId = $userModel->createUser(
            $data['email'],
            $data['phone'],
            $data['password'],
            $data['role'],
            $referrer_id
        );

        if (!$userId) {
            http_response_code(500); // Internal Server Error
            return ["status" => "error", "message" => "Failed to create account"];
        }

        $profileCreated = $userModel->createUserProfile(
            $userId,
            $data['first_name'],
            $data['last_name']
        );

        if (!$profileCreated) {
            http_response_code(500); // Internal Server Error
            return ["status" => "error", "message" => "Failed to create user profile"];
        }

        http_response_code(201); // Created
        return ["status" => "success", "message" => "Account created successfully", "user_id" => $userId];
    }
*/
    public function login($data)
    {
        $userModel = new User();
        $phone = $data['phone'];
        $password = $data['password'];

        // Input validation for missing data
        if (empty($phone) || empty($password)) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Phone and password are required"];
        }

        // Fetch user by phone number
        $user = $userModel->login($phone, $password);

        // Handle invalid credentials
        if (!$user) {
            http_response_code(401); // Unauthorized
            return ["status" => "error", "message" => "Invalid credentials"];
        }

        // JWT token generation with expiration
        $jwt = new JwtHandler();
        $payload = ["user_id" => $user['id'], "role" => $user['role']];
        $token = $jwt->encode($payload);

        http_response_code(200); // OK
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
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Email is required"];
        }

        $existingUser = $userModel->getUserByEmail($data['email']);
        if ($existingUser) {
            http_response_code(409); // Conflict
            return ["status" => "exists", "message" => "User already exists", "user" => $existingUser];
        }

        http_response_code(200); // OK
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
            http_response_code(401); // Unauthorized
            return ["status" => "error", "message" => "Invalid OTP"];
        }

        http_response_code(200); // OK
        return ["status" => "success", "message" => "OTP verified"];
    }

    public function finalizeSignup($data)
{
    $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "$field is required"];
        }
    }

    if ($data['password'] !== $data['confirm_password']) {
        http_response_code(400);
        return ["status" => "error", "message" => "Passwords do not match"];
    }

    // Normalize phone to E.164 format
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

    // Check if OTP is verified for this phone (OPTIONAL: with 'signup' purpose)
    $otpModel = new Otp();
    $isVerified = $otpModel->isOtpVerified($phone, 'signup'); // You must implement this in the Otp model

    if (!$isVerified) {
        http_response_code(401);
        return ["status" => "error", "message" => "OTP not verified. Please verify OTP before signing up."];
    }

    // Handle referral
    $referrer_id = null;
    if (!empty($data['referral_code'])) {
        $referrer = $userModel->getUserByReferralCode($data['referral_code']);
        if (!$referrer) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid referral code"];
        }
        $referrer_id = $referrer['id'];
    }

    $userId = $userModel->createUser(
        $data['email'],
        $phone,
        $data['password'],
        $data['role'],
        $referrer_id
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

    http_response_code(201);
    return [
        "status" => "success",
        "message" => "Account created successfully",
        "user_id" => $userId
    ];
}

}
