<?php
namespace Config;

use Dotenv\Dotenv;


class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function getConnection()
    {
        // Try to load .env file, but don't fail if it doesn't exist
        $envPath = __DIR__."/../../.env";
        if (file_exists($envPath)) {
            $dotenv = Dotenv::createImmutable(__DIR__."/../../");
            $dotenv->load();
        }

        $this->conn = null;
        
        // Use environment variables if available, otherwise use fallback values
        $this->host = $_ENV["DB_HOST"] ?? "localhost";
        $this->db_name = $_ENV["DB_NAME"] ?? "u232647434_db";
        $this->username = $_ENV["DB_USERNAME"] ?? "u232647434_user";
        $this->password = $_ENV["DB_PASSWORD"] ?? "#Uti*odpl4B8";

        try {
            $this->conn = new \PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new \Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
