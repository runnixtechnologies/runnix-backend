<?php

namespace Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;
use Config\Database;
use PDO;
class JwtHandler {
    private $secret;
    private $issuedAt;
    private $expire;
    private $conn;

    public function __construct() {
        // Load .env file with error handling
        if (file_exists(__DIR__ . '/../.env')) {
            try {
                $dotenv = Dotenv::createImmutable(dirname(__DIR__));
                $dotenv->load();
            } catch (\Exception $e) {
                error_log("Failed to load .env file in JwtHandler: " . $e->getMessage());
                // Continue without .env file - use default values
            }
        }

        $this->secret = "fvdhgfhdfhdvhfdhfdhfdhfhdfdvhfgdvfhdvfghdvhfdvhfdvhfdvhfd";// $_ENV['JWT_SECRET'] ?? 'fallback_secret_key';
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + (30 * 24 * 60 * 60); // 30 days (no timeout)

        // Use Config\Database to get DB connection
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function encode($payload) {
        $token = JWT::encode(array_merge($payload, [
            'iat' => $this->issuedAt,
            'exp' => $this->expire
        ]), $this->secret, 'HS256');
        return $token;
    }

    public function decode($token) {
        try {
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($token)) {
                return false;
            }

            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $payload = (array)$decoded;
            
            // Session timeout disabled - no inactivity-based session expiry
            // Users will stay logged in until they manually logout or token expires (30 days)
            
            return $payload;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Save token in blacklist
    public function blacklistToken($token) {
        // Decode token to get expiry time
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $expiresAt = date('Y-m-d H:i:s', $decoded->exp);
        } catch (\Exception $e) {
            // If we can't decode, set expiry to current time + 30 days
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        }
        
        $sql = "INSERT INTO blacklisted_tokens (token, expires_at) VALUES (:token, :expires_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        return $stmt->execute();
    }

    // Check if token is blacklisted
    public function isTokenBlacklisted($token) {
        $sql = "SELECT 1 FROM blacklisted_tokens WHERE token = :token LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
