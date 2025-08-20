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
        $stmt = $this->conn->prepare("SELECT id, name, image_url FROM store_types WHERE status = '1'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveCategories()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM categories WHERE status = '1'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchActiveCategoriesByStoreType($storeTypeId)
    {
        $query = "SELECT id, name 
                  FROM categories 
                  WHERE status = 1 AND store_type_id = :store_type_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':store_type_id', $storeTypeId, PDO::PARAM_INT);
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

    public function storeIDExists($store_id) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id = :store_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $store_id]);
        return $stmt->fetchColumn() > 0;
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

    public function updateStoreStatus($storeId, $isOnline) {
        try {
            $sql = "INSERT INTO store_status (store_id, is_online)
                    VALUES (:store_id, :is_online)
                    ON DUPLICATE KEY UPDATE is_online = :is_online, updated_at = NOW()";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':store_id' => $storeId,
                ':is_online' => $isOnline
            ]);

            http_response_code(200);
            return ["status" => "success", "message" => "Store status updated successfully."];

        } catch (\PDOException $e) {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update store status."];
        }
    }

    public function getStoreStatus($storeId) {
        try {
            $sql = "SELECT is_online FROM store_status WHERE store_id = :store_id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':store_id' => $storeId]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($status) {
                return ["status" => "success", "data" => $status];
            } else {
                return ["status" => "error", "message" => "Store status not found."];
            }

        } catch (\PDOException $e) {
            return ["status" => "error", "message" => "Failed to fetch store status."];
        }
    }

    // Operating Hours Methods
    public function getOperatingHours($storeId)
    {
        try {
            $sql = "SELECT day_of_week, open_time, close_time, is_closed 
                    FROM store_operating_hours 
                    WHERE store_id = :store_id 
                    ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['store_id' => $storeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to associative array with day as key
            $operatingHours = [];
            foreach ($results as $row) {
                $operatingHours[$row['day_of_week']] = [
                    'is_closed' => (bool)$row['is_closed'],
                    'open_time' => $row['open_time'],
                    'close_time' => $row['close_time']
                ];
            }
            
            return $operatingHours;
        } catch (PDOException $e) {
            error_log("getOperatingHours error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateOperatingHours($storeId, $operatingHours)
    {
        try {
            $this->conn->beginTransaction();
            
            // Delete existing operating hours for this store
            $deleteSql = "DELETE FROM store_operating_hours WHERE store_id = :store_id";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute(['store_id' => $storeId]);
            
            // Insert new operating hours
            $insertSql = "INSERT INTO store_operating_hours (store_id, day_of_week, open_time, close_time, is_closed) 
                         VALUES (:store_id, :day_of_week, :open_time, :close_time, :is_closed)";
            $insertStmt = $this->conn->prepare($insertSql);
            
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $dayData = $operatingHours[$day] ?? ['is_closed' => true, 'open_time' => null, 'close_time' => null];
                
                $insertStmt->execute([
                    'store_id' => $storeId,
                    'day_of_week' => $day,
                    'open_time' => $dayData['is_closed'] ? null : $dayData['open_time'],
                    'close_time' => $dayData['is_closed'] ? null : $dayData['close_time'],
                    'is_closed' => $dayData['is_closed']
                ]);
            }
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("updateOperatingHours error: " . $e->getMessage());
            return false;
        }
    }
    
    public function storeHasOperatingHours($storeId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM store_operating_hours WHERE store_id = :store_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['store_id' => $storeId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("storeHasOperatingHours error: " . $e->getMessage());
            return false;
        }
    }
}

