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

    public function createStore($userId, $name, $address, $email, $phone, $regNumber, $logoFilename = null)
    {
        $sql = "INSERT INTO stores (user_id, biz_name, store_address, biz_email, biz_phone, biz_reg_number, biz_logo)
                VALUES (:user_id, :biz_name, :store_address, :biz_email, :biz_phone, :biz_reg_number, :biz_logo)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':biz_name' => $name,
            ':store_address' => $address,
            ':biz_email' => $email,
            ':biz_phone' => $phone,
            ':biz_reg_number' => $regNumber,
            ':biz_logo' => $logoFilename
        ]);
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
