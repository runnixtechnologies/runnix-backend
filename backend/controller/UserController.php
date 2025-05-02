<?php
namespace Controller;

use Model\User;
use Model\Otp;
use Config\JwtHandler;

class UserController
{
    public function signup($data)
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
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Passwords do not match"];
        }

        if (!in_array($data['role'], ['user', 'merchant', 'rider'])) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Invalid role"];
        }

        $userModel = new User();

        if ($userModel->getUserByEmail($data['email'])) {
            http_response_code(409); // Conflict
            return ["status" => "error", "message" => "Email already exists"];
        }

        if ($userModel->getUserByPhone($data['phone'])) {
            http_response_code(409); // Conflict
            return ["status" => "error", "message" => "Phone already exists"];
        }

        $referrer_id = null;
        if ($data['role'] === 'user' && !empty($data['referral_code'])) {
            $ref = $userModel->getUserByReferralCode($data['referral_code']);
            if (!$ref) {
                http_response_code(400); // Bad Request
                return ["status" => "error", "message" => "Invalid referral code"];
            }
            $referrer_id = $ref['id'];
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
            return ["status" => "error", "message" => "Account creation failed"];
        }

        $profileCreated = $userModel->createUserProfile($userId, $data['first_name'], $data['last_name']);

        if (!$profileCreated) {
            http_response_code(500); // Internal Server Error
            return ["status" => "error", "message" => "Failed to create profile"];
        }

        http_response_code(201); // Created
        return ["status" => "success", "message" => "Account created successfully", "user_id" => $userId];
    }

    public function logout($data)
    {
        if (empty($data['token'])) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Token is required"];
        }

        $jwtHandler = new JwtHandler();

        // Check if the token is valid
        if ($jwtHandler->isTokenBlacklisted($data['token'])) {
            http_response_code(400); // Bad Request
            return ["status" => "error", "message" => "Token is already invalidated"];
        }

        $decoded = $jwtHandler->decode($data['token']);
        if (!$decoded) {
            http_response_code(401); // Unauthorized
            return ["status" => "error", "message" => "Invalid or expired token"];
        }

        // Blacklist the token to invalidate it
        if ($jwtHandler->blacklistToken($data['token'])) {
            http_response_code(200); // OK
            return [
                "status" => "success",
                "message" => "Logged out successfully"
            ];
        } else {
            http_response_code(500); // Internal Server Error
            return [
                "status" => "error",
                "message" => "Could not logout. Try again."
            ];
        }
    }
}
