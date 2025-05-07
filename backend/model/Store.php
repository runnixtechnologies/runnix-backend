<?php
namespace Model;

use Config\Database;
use PDO;

class Store
{
    private $conn;
    private $table = "stores";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function createStore($userId, $storeName, $bizAddress, $bizEmail, $bizPhone, $bizRegNumber, $bizLogo = null, $storeType)
    {
        try {
            $sql = "INSERT INTO stores (user_id, store_name, biz_address, biz_email, biz_phone, biz_reg_number, biz_logo, store_type)
                    VALUES (:user_id, :store_name, :biz_address, :biz_email, :biz_phone, :biz_reg_number, :biz_logo, :store_type)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':store_name' => $storeName,
                ':biz_address' => $bizAddress,
                ':biz_email' => $bizEmail,
                ':biz_phone' => $bizPhone,
                ':biz_reg_number' => $bizRegNumber,
                ':biz_logo' => $bizLogo,
                ':store_type' => $storeType
            ]);
    
            return true;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "DB Error: " . $e->getMessage()
            ]);
            exit;
        }
    }
    
    

    public function getStoreByUserId($user_id)
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function storeExists($field, $value)
{
    $allowedFields = ['biz_name', 'biz_email', 'biz_phone', 'biz_reg_number'];
    if (!in_array($field, $allowedFields)) {
        throw new \InvalidArgumentException("Invalid field for storeExists check");
    }

    $sql = "SELECT COUNT(*) FROM stores WHERE {$field} = :value";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':value' => $value]);
    return $stmt->fetchColumn() > 0;
}



public function deleteStoreByUserId($userId)
{
    $stmt = $this->conn->prepare("DELETE FROM stores WHERE user_id = ?");
    return $stmt->execute([$userId]);
}


}
