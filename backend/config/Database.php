<?php
namespace Config;

class Database
{
    private $host = "localhost";
    private $db_name = "u232647434_db";
    private $username = "u232647434_user";
    private $password = "#Uti*odpl4B8";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

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
