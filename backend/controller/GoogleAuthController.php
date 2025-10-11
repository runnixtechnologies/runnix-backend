<?php
namespace Controller;

use Config\Database;
use Firebase\JWT\JWT;

class GoogleAuthController
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function handleGoogleLogin($googleUserData)
    {
        // Extract data received from Google
        $firstName = $googleUserData['firstName'] ?? null;
        $lastName = $googleUserData['lastName'] ?? null;
        $email = $googleUserData['email'] ?? null;
        $phone = $googleUserData['phone'] ?? null;

        if (!$email) {
            return ["status" => "error", "message" => "Email is required from Google"];
        }

        // Check if user already exists
        $query = "SELECT id, email FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $existingUser = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingUser) {
            // User exists → maybe login directly or force completing profile later
            return [
                "status" => "exists",
                "message" => "User already exists",
                "user" => $existingUser
            ];
        }

        // If user does not exist yet, return the prefilled data for frontend
        return [
            "status" => "prefill",
            "message" => "Prefill registration form",
            "data" => [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "phone" => $phone
            ]
        ];
    }
}
?>
