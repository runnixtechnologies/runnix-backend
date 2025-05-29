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

    public function createStore($userId, $storeName, $bizAddress, $bizEmail, $bizPhone, $bizRegNumber, $storeTypeId, $bizLogo = null)

    {
        try {
            $sql = "INSERT INTO stores (user_id, store_name, biz_address, biz_email, biz_phone, biz_reg_number, biz_logo, store_type_id)
                    VALUES (:user_id, :store_name, :biz_address, :biz_email, :biz_phone, :biz_reg_number, :biz_logo, :store_type_id)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':store_name' => $storeName,
                ':biz_address' => $bizAddress,
                ':biz_email' => $bizEmail,
                ':biz_phone' => $bizPhone,
                ':biz_reg_number' => $bizRegNumber,
                ':biz_logo' => $bizLogo,
                ':store_type_id' => $storeTypeId
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
    
    
   
public function getStoreTypes()
{
    $stmt = $this->conn->prepare("SELECT id, name, image_url FROM store_types WHERE status = 1");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $allowedFields = ['store_name', 'biz_email', 'biz_phone', 'biz_reg_number'];
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

public function saveVerificationAddress($storeId, $latitude, $longitude)
{
    // Check for existing pending verification
    $checkSql = "SELECT * FROM store_verification_addresses WHERE store_id = :store_id AND verification_status = 'pending'";
    $stmt = $this->conn->prepare($checkSql);
    $stmt->execute([':store_id' => $storeId]);

    if ($stmt->rowCount() > 0) {
        return false; // Pending request already exists
    }

    // Insert new verification request
    $sql = "INSERT INTO store_verification_addresses 
        (store_id, latitude, longitude, verified_by_user, verification_status, created_at, updated_at)
        VALUES (:store_id, :latitude, :longitude, 1, 'pending', NOW(), NOW())";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':store_id' => $storeId,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);
}


public function verifyAddressByAdmin($verificationId, $action)
{
    $status = $action === 'approve' ? 'verified' : 'rejected';

    $sql = "UPDATE store_verification_addresses 
            SET verification_status = :status, 
                verified_by_admin = 1, 
                verification_date = NOW(), 
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':status' => $status,
        ':id' => $verificationId
    ]);
}

public function getStoreById($storeId)
{
    $sql = "SELECT * FROM stores WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':id' => $storeId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
