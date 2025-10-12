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
        $dotenv = Dotenv::createImmutable(__DIR__."/../../");
        $dotenv->load();

        $this->conn = null;
        $this->host = $_ENV["DB_HOST"];
        $this->db_name = $_ENV["DB_NAME"];
        $this->username = $_ENV["DB_USERNAME"];
        $this->password = $_ENV["DB_PASSWORD"];

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
