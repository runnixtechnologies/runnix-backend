<?php
namespace Model;

use Config\Database;
use PDO;

class PackageDelivery
{
    private $conn;
    private $table = "package_deliveries";

    public function __construct()
    {
        $this->conn = (new \Config\Database())->getConnection();
    }

    /**
     * Generate unique package number
     */
    private function generatePackageNumber()
    {
        $prefix = 'PKG';
        $timestamp = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }

    /**
     * Create package delivery request
     */
    public function createPackageDelivery($data)
    {
        try {
            $this->conn->beginTransaction();

            // Generate package number
            $packageNumber = $this->generatePackageNumber();

            // Insert package delivery
            $sql = "INSERT INTO {$this->table} 
                    (package_number, sender_id, receiver_name, receiver_phone, receiver_address,
                     receiver_latitude, receiver_longitude, package_description, package_value,
                     delivery_fee, insurance_fee, pickup_instructions, delivery_instructions,
                     status, payment_status)
                    VALUES (:package_number, :sender_id, :receiver_name, :receiver_phone, :receiver_address,
                            :receiver_latitude, :receiver_longitude, :package_description, :package_value,
                            :delivery_fee, :insurance_fee, :pickup_instructions, :delivery_instructions,
                            'pending', 'pending')";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':package_number', $packageNumber);
            $stmt->bindParam(':sender_id', $data['sender_id']);
            $stmt->bindParam(':receiver_name', $data['receiver_name']);
            $stmt->bindParam(':receiver_phone', $data['receiver_phone']);
            $stmt->bindParam(':receiver_address', $data['receiver_address']);
            $stmt->bindParam(':receiver_latitude', $data['receiver_latitude']);
            $stmt->bindParam(':receiver_longitude', $data['receiver_longitude']);
            $stmt->bindParam(':package_description', $data['package_description']);
            $stmt->bindParam(':package_value', $data['package_value']);
            $stmt->bindParam(':delivery_fee', $data['delivery_fee']);
            $stmt->bindParam(':insurance_fee', $data['insurance_fee']);
            $stmt->bindParam(':pickup_instructions', $data['pickup_instructions']);
            $stmt->bindParam(':delivery_instructions', $data['delivery_instructions']);

            if (!$stmt->execute()) {
                throw new \Exception("Failed to create package delivery");
            }

            $packageId = $this->conn->lastInsertId();

            $this->conn->commit();
            return $packageId;

        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("Package delivery creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get package deliveries for user
     */
    public function getUserPackageDeliveries($userId, $status = null, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT pd.*, 
                       up.first_name as sender_first_name,
                       up.last_name as sender_last_name,
                       r.first_name as rider_first_name,
                       r.last_name as rider_last_name,
                       r.phone as rider_phone
                FROM {$this->table} pd
                LEFT JOIN user_profiles up ON pd.sender_id = up.user_id
                LEFT JOIN user_profiles r ON pd.rider_id = r.user_id
                WHERE pd.sender_id = :user_id";
        
        $params = [':user_id' => $userId];
        
        if ($status) {
            $sql .= " AND pd.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY pd.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get package delivery details
     */
    public function getPackageDeliveryDetails($packageId)
    {
        $sql = "SELECT pd.*, 
                       up.first_name as sender_first_name,
                       up.last_name as sender_last_name,
                       up.phone as sender_phone,
                       up.email as sender_email,
                       r.first_name as rider_first_name,
                       r.last_name as rider_last_name,
                       r.phone as rider_phone
                FROM {$this->table} pd
                LEFT JOIN user_profiles up ON pd.sender_id = up.user_id
                LEFT JOIN user_profiles r ON pd.rider_id = r.user_id
                WHERE pd.id = :package_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':package_id', $packageId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update package status
     */
    public function updatePackageStatus($packageId, $status, $riderId = null, $notes = null)
    {
        try {
            $this->conn->beginTransaction();
            
            $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW()";
            
            // Add rider if provided
            if ($riderId) {
                $sql .= ", rider_id = :rider_id";
            }
            
            // Add timestamp based on status
            switch ($status) {
                case 'accepted':
                    $sql .= ", rider_id = :rider_id";
                    break;
                case 'picked_up':
                    $sql .= ", picked_up_at = NOW()";
                    break;
                case 'delivered':
                    $sql .= ", delivered_at = NOW()";
                    break;
                case 'cancelled':
                    $sql .= ", cancelled_at = NOW()";
                    break;
            }
            
            $sql .= " WHERE id = :package_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':package_id', $packageId);
            
            if ($riderId) {
                $stmt->bindParam(':rider_id', $riderId);
            }
            
            if (!$stmt->execute()) {
                throw new \Exception("Failed to update package status");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("Package status update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get package by package number
     */
    public function getPackageByNumber($packageNumber)
    {
        $sql = "SELECT * FROM {$this->table} WHERE package_number = :package_number";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':package_number', $packageNumber);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get packages count for user
     */
    public function getUserPackageCount($userId, $status = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE sender_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Cancel package delivery
     */
    public function cancelPackageDelivery($packageId, $userId, $reason = null)
    {
        try {
            // Check if package can be cancelled
            $package = $this->getPackageDeliveryDetails($packageId);
            if (!$package) {
                return false;
            }
            
            if ($package['sender_id'] != $userId) {
                return false; // Only sender can cancel
            }
            
            if (in_array($package['status'], ['delivered', 'cancelled'])) {
                return false; // Cannot cancel delivered or already cancelled packages
            }
            
            return $this->updatePackageStatus($packageId, 'cancelled', null, $reason);
            
        } catch (\Exception $e) {
            error_log("Cancel package delivery error: " . $e->getMessage());
            return false;
        }
    }
}
