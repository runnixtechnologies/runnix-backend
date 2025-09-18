<?php

namespace Controller;

use Model\User;
use Exception;

class SupportController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Verify support pin and return user information for support authentication
     */
    public function verifySupportPin($supportPin, $userIdentifier = null)
    {
        try {
            // Validate support pin format (8 characters: 4 letters + 4 numbers)
            if (!$this->validateSupportPinFormat($supportPin)) {
                http_response_code(400);
                return [
                    "status" => "error",
                    "message" => "Invalid support pin format. Support pin should be 8 characters (4 letters + 4 numbers)"
                ];
            }

            // Get user by support pin
            $user = $this->userModel->getUserBySupportPin($supportPin);
            
            if (!$user) {
                http_response_code(404);
                return [
                    "status" => "error",
                    "message" => "Invalid support pin. Please check your support pin and try again."
                ];
            }

            // If user identifier is provided, verify it matches the user
            if ($userIdentifier) {
                $identifierMatch = $this->verifyUserIdentifier($user, $userIdentifier);
                if (!$identifierMatch) {
                    http_response_code(400);
                    return [
                        "status" => "error",
                        "message" => "Support pin does not match the provided user information."
                    ];
                }
            }

            // Return user information for support team
            http_response_code(200);
            return [
                "status" => "success",
                "message" => "Support pin verified successfully",
                "user" => [
                    "id" => $user['id'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "role" => $user['role'],
                    "is_verified" => $user['is_verified'],
                    "status" => $user['status'],
                    "created_at" => $user['created_at']
                ]
            ];

        } catch (Exception $e) {
            error_log("Support pin verification error: " . $e->getMessage());
            http_response_code(500);
            return [
                "status" => "error",
                "message" => "An error occurred while verifying support pin. Please try again."
            ];
        }
    }

    /**
     * Verify support pin for a specific user ID
     */
    public function verifySupportPinForUser($userId, $supportPin)
    {
        try {
            // Validate support pin format
            if (!$this->validateSupportPinFormat($supportPin)) {
                http_response_code(400);
                return [
                    "status" => "error",
                    "message" => "Invalid support pin format. Support pin should be 8 characters (4 letters + 4 numbers)"
                ];
            }

            // Verify support pin for the specific user
            $isValid = $this->userModel->verifySupportPin($userId, $supportPin);
            
            if (!$isValid) {
                http_response_code(400);
                return [
                    "status" => "error",
                    "message" => "Invalid support pin for this user account."
                ];
            }

            // Get user information
            $user = $this->userModel->getUserById($userId);
            
            if (!$user) {
                http_response_code(404);
                return [
                    "status" => "error",
                    "message" => "User not found."
                ];
            }

            http_response_code(200);
            return [
                "status" => "success",
                "message" => "Support pin verified successfully",
                "user" => [
                    "id" => $user['id'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "role" => $user['role'],
                    "is_verified" => $user['is_verified'],
                    "status" => $user['status'],
                    "created_at" => $user['created_at']
                ]
            ];

        } catch (Exception $e) {
            error_log("Support pin verification error: " . $e->getMessage());
            http_response_code(500);
            return [
                "status" => "error",
                "message" => "An error occurred while verifying support pin. Please try again."
            ];
        }
    }

    /**
     * Regenerate support pin for a user
     */
    public function regenerateSupportPin($userId)
    {
        try {
            // Check if user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                http_response_code(404);
                return [
                    "status" => "error",
                    "message" => "User not found."
                ];
            }

            // Regenerate support pin
            $newPin = $this->userModel->regenerateSupportPin($userId);
            
            if (!$newPin) {
                http_response_code(500);
                return [
                    "status" => "error",
                    "message" => "Failed to regenerate support pin. Please try again."
                ];
            }

            http_response_code(200);
            return [
                "status" => "success",
                "message" => "Support pin regenerated successfully",
                "support_pin" => $newPin
            ];

        } catch (Exception $e) {
            error_log("Support pin regeneration error: " . $e->getMessage());
            http_response_code(500);
            return [
                "status" => "error",
                "message" => "An error occurred while regenerating support pin. Please try again."
            ];
        }
    }

    /**
     * Get support pin for a user (for display purposes)
     */
    public function getSupportPin($userId)
    {
        try {
            $user = $this->userModel->getUserById($userId);
            
            if (!$user) {
                http_response_code(404);
                return [
                    "status" => "error",
                    "message" => "User not found."
                ];
            }

            if (!isset($user['support_pin'])) {
                http_response_code(404);
                return [
                    "status" => "error",
                    "message" => "Support pin not found for this user."
                ];
            }

            http_response_code(200);
            return [
                "status" => "success",
                "support_pin" => $user['support_pin']
            ];

        } catch (Exception $e) {
            error_log("Get support pin error: " . $e->getMessage());
            http_response_code(500);
            return [
                "status" => "error",
                "message" => "An error occurred while retrieving support pin. Please try again."
            ];
        }
    }

    /**
     * Validate support pin format (8 characters: 4 letters + 4 numbers)
     */
    private function validateSupportPinFormat($supportPin)
    {
        // Check if pin is exactly 8 characters
        if (strlen($supportPin) !== 8) {
            return false;
        }

        // Check if first 4 characters are letters
        $letters = substr($supportPin, 0, 4);
        if (!ctype_alpha($letters)) {
            return false;
        }

        // Check if last 4 characters are numbers
        $numbers = substr($supportPin, 4, 4);
        if (!ctype_digit($numbers)) {
            return false;
        }

        return true;
    }

    /**
     * Verify user identifier (email or phone) matches the user
     */
    private function verifyUserIdentifier($user, $identifier)
    {
        // Check if identifier matches email or phone
        if (strtolower(trim($identifier)) === strtolower(trim($user['email']))) {
            return true;
        }

        if (trim($identifier) === trim($user['phone'])) {
            return true;
        }

        return false;
    }
}
