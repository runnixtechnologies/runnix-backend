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
        // Load .env file
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }

        $this->secret = $_ENV['JWT_SECRET'] ?? 'fallback_secret_key';
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + 3600; // 1 hour

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
            return (array)$decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Save token in blacklist
    public function blacklistToken($token) {
        $sql = "INSERT INTO blacklisted_tokens (token) VALUES (:token)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
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
