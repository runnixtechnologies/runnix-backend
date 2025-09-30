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
        error_log("Store::getStoreByUserId - Searching for store with user_id: $user_id");
        $sql = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Store::getStoreByUserId - Query result: " . json_encode($result));
        return $result;
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
        $sql = "SELECT s.*, st.name AS store_type_name 
                FROM stores s 
                LEFT JOIN store_types st ON s.store_type_id = st.id 
                WHERE s.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $storeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get stores for customer with filtering and sorting
     */
    public function getStoresForCustomer($storeTypeId = null, $search = null, $userLocation = null, $sort = 'popular', $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT s.*, st.name AS store_type_name, 
                           COALESCE(AVG(r.rating), 0) AS rating,
                           COUNT(r.id) AS review_count,
                           ss.is_online
                    FROM stores s 
                    LEFT JOIN store_types st ON s.store_type_id = st.id 
                    LEFT JOIN reviews r ON s.id = r.store_id
                    LEFT JOIN store_status ss ON s.id = ss.store_id
                    WHERE (s.status = 'active' OR s.status = '1' OR s.status = 1)
                      AND (st.status = '1' OR st.status = 1)";
            
            $params = [];
            
            // Filter by store type
            if ($storeTypeId) {
                $sql .= " AND s.store_type_id = :store_type_id";
                $params[':store_type_id'] = $storeTypeId;
            }
            
            // Search filter
            if ($search) {
                $sql .= " AND (s.store_name LIKE :search OR s.biz_address LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            $sql .= " GROUP BY s.id";
            
            // Add distance calculation if user location is available
            if ($userLocation && isset($userLocation['latitude']) && isset($userLocation['longitude'])) {
                $sql = "SELECT *, 
                               (6371 * acos(cos(radians(:user_lat)) * cos(radians(s.latitude)) * 
                                cos(radians(s.longitude) - radians(:user_lng)) + 
                                sin(radians(:user_lat)) * sin(radians(s.latitude)))) AS distance
                        FROM ($sql) s";
                $params[':user_lat'] = $userLocation['latitude'];
                $params[':user_lng'] = $userLocation['longitude'];
            }
            
            // Sorting
            switch ($sort) {
                case 'newest':
                    $sql .= " ORDER BY s.created_at DESC";
                    break;
                case 'closest':
                    if ($userLocation) {
                        $sql .= " ORDER BY distance ASC";
                    } else {
                        $sql .= " ORDER BY s.created_at DESC";
                    }
                    break;
                case 'popular':
                default:
                    $sql .= " ORDER BY rating DESC, review_count DESC";
                    break;
            }
            
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->conn->prepare($sql);

            // Bind parameters with correct types (especially for LIMIT/OFFSET)
            foreach ($params as $name => $value) {
                if ($name === ':limit' || $name === ':offset') {
                    $stmt->bindValue($name, (int)$value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($name, $value);
                }
            }

            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Error getting stores for customer: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of stores for customer
     */
    public function getStoresForCustomerCount($storeTypeId = null, $search = null)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT s.id) as total
                    FROM stores s 
                    LEFT JOIN store_types st ON s.store_type_id = st.id
                    WHERE (s.status = 'active' OR s.status = '1' OR s.status = 1)
                      AND (st.status = '1' OR st.status = 1)";
            
            $params = [];
            
            // Filter by store type
            if ($storeTypeId) {
                $sql .= " AND s.store_type_id = :store_type_id";
                $params[':store_type_id'] = $storeTypeId;
            }
            
            // Search filter
            if ($search) {
                $sql .= " AND (s.store_name LIKE :search OR s.biz_address LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['total'];
            
        } catch (\PDOException $e) {
            error_log("Error getting stores count for customer: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get connection for external use
     */
    public function getConnection()
    {
        return $this->conn;
    }

    public function getAllStores()
    {
        $sql = "SELECT s.*, st.name AS store_type_name 
                FROM stores s 
                LEFT JOIN store_types st ON s.store_type_id = st.id 
                ORDER BY s.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    // Update store profile information
    public function updateStoreProfile($storeId, $data)
    {
        try {
            $this->conn->beginTransaction();
            
            $updateFields = [];
            $params = ['store_id' => $storeId];
            
            // Build dynamic update query based on provided fields
            if (isset($data['store_name'])) {
                $updateFields[] = "store_name = :store_name";
                $params['store_name'] = $data['store_name'];
            }
            
            if (isset($data['biz_address'])) {
                $updateFields[] = "biz_address = :biz_address";
                $params['biz_address'] = $data['biz_address'];
            }
            
            if (isset($data['biz_email'])) {
                $updateFields[] = "biz_email = :biz_email";
                $params['biz_email'] = $data['biz_email'];
            }
            
            if (isset($data['biz_phone'])) {
                $updateFields[] = "biz_phone = :biz_phone";
                $params['biz_phone'] = $data['biz_phone'];
            }
            
            if (isset($data['biz_reg_number'])) {
                $updateFields[] = "biz_reg_number = :biz_reg_number";
                $params['biz_reg_number'] = $data['biz_reg_number'];
            }
            
            if (isset($data['biz_logo'])) {
                $updateFields[] = "biz_logo = :biz_logo";
                $params['biz_logo'] = $data['biz_logo'];
            }
            
            if (empty($updateFields)) {
                $this->conn->rollBack();
                return false;
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :store_id";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
            
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Update store profile error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if store field value already exists (for uniqueness validation)
    public function storeFieldExists($field, $value, $excludeStoreId = null)
    {
        $allowedFields = ['store_name', 'biz_email', 'biz_phone', 'biz_reg_number'];
        if (!in_array($field, $allowedFields)) {
            throw new \InvalidArgumentException("Invalid field for storeFieldExists check");
        }

        $sql = "SELECT COUNT(*) FROM stores WHERE {$field} = :value";
        $params = [':value' => $value];
        
        if ($excludeStoreId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeStoreId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    // Operating Hours Methods
    public function getOperatingHours($storeId)
    {
        try {
            // Check if business_24_7 column exists in stores table
            $checkBusiness247Sql = "SHOW COLUMNS FROM stores LIKE 'business_24_7'";
            $checkBusiness247Stmt = $this->conn->prepare($checkBusiness247Sql);
            $checkBusiness247Stmt->execute();
            $hasBusiness247Column = $checkBusiness247Stmt->rowCount() > 0;
            
            if ($hasBusiness247Column) {
                // Get business 24/7 setting from stores table
                $business247Sql = "SELECT business_24_7 FROM stores WHERE id = :store_id";
                $business247Stmt = $this->conn->prepare($business247Sql);
                $business247Stmt->execute(['store_id' => $storeId]);
                $business247 = $business247Stmt->fetchColumn();
            } else {
                $business247 = false; // Default to false if column doesn't exist
            }
            
            // Check if new columns exist in the table
            $checkColumnsSql = "SHOW COLUMNS FROM store_operating_hours LIKE 'enabled'";
            $checkStmt = $this->conn->prepare($checkColumnsSql);
            $checkStmt->execute();
            $hasNewColumns = $checkStmt->rowCount() > 0;
            
            if ($hasNewColumns) {
                $sql = "SELECT day_of_week, open_time, close_time, is_closed, enabled, is_24hrs 
                        FROM store_operating_hours 
                        WHERE store_id = :store_id 
                        ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
            } else {
            $sql = "SELECT day_of_week, open_time, close_time, is_closed 
                    FROM store_operating_hours 
                    WHERE store_id = :store_id 
                    ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['store_id' => $storeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to associative array with day as key
            $operatingHours = [];
            foreach ($results as $row) {
                // Handle backward compatibility - check if new columns exist
                $enabled = isset($row['enabled']) ? (bool)$row['enabled'] : !(bool)$row['is_closed'];
                $is24hrs = isset($row['is_24hrs']) ? (bool)$row['is_24hrs'] : false;
                
                $operatingHours[$row['day_of_week']] = [
                    'enabled' => $enabled,
                    'is_24hrs' => $is24hrs,
                    'is_closed' => (bool)$row['is_closed'],
                    'open_time' => $row['open_time'],
                    'close_time' => $row['close_time']
                ];
            }
            
            return [
                'business_24_7' => (bool)$business247,
                'operating_hours' => $operatingHours
            ];
        } catch (PDOException $e) {
            error_log("getOperatingHours error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateOperatingHours($storeId, $operatingHours, $business247 = false)
    {
        try {
            $this->conn->beginTransaction();
            
            // Check if business_24_7 column exists in stores table
            $checkBusiness247Sql = "SHOW COLUMNS FROM stores LIKE 'business_24_7'";
            $checkBusiness247Stmt = $this->conn->prepare($checkBusiness247Sql);
            $checkBusiness247Stmt->execute();
            $hasBusiness247Column = $checkBusiness247Stmt->rowCount() > 0;
            
            if ($hasBusiness247Column) {
                // Update business 24/7 setting in stores table
                $updateBusiness247Sql = "UPDATE stores SET business_24_7 = :business_24_7 WHERE id = :store_id";
                $updateBusiness247Stmt = $this->conn->prepare($updateBusiness247Sql);
                $updateBusiness247Stmt->execute([
                    'business_24_7' => $business247,
                    'store_id' => $storeId
                ]);
            }
            
            // Delete existing operating hours for this store
            $deleteSql = "DELETE FROM store_operating_hours WHERE store_id = :store_id";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute(['store_id' => $storeId]);
            
            // Check if new columns exist in store_operating_hours table
            $checkColumnsSql = "SHOW COLUMNS FROM store_operating_hours LIKE 'enabled'";
            $checkStmt = $this->conn->prepare($checkColumnsSql);
            $checkStmt->execute();
            $hasNewColumns = $checkStmt->rowCount() > 0;
            
            if ($hasNewColumns) {
                // Insert new operating hours with new columns
                $insertSql = "INSERT INTO store_operating_hours (store_id, day_of_week, open_time, close_time, is_closed, enabled, is_24hrs) 
                             VALUES (:store_id, :day_of_week, :open_time, :close_time, :is_closed, :enabled, :is_24hrs)";
            } else {
                // Insert new operating hours with old columns only
            $insertSql = "INSERT INTO store_operating_hours (store_id, day_of_week, open_time, close_time, is_closed) 
                         VALUES (:store_id, :day_of_week, :open_time, :close_time, :is_closed)";
            }
            $insertStmt = $this->conn->prepare($insertSql);
            
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $dayData = $operatingHours[$day] ?? [
                    'enabled' => false,
                    'is_24hrs' => false,
                    'is_closed' => true,
                    'open_time' => null,
                    'close_time' => null
                ];
                
                // If business is 24/7, set all enabled days to 24hrs
                if ($business247 && $dayData['enabled']) {
                    $dayData['is_24hrs'] = true;
                    $dayData['is_closed'] = false;
                    $dayData['open_time'] = '00:00:00';
                    $dayData['close_time'] = '23:59:59';
                }
                
                if ($hasNewColumns) {
                    $insertStmt->execute([
                        'store_id' => $storeId,
                        'day_of_week' => $day,
                        'open_time' => $dayData['enabled'] && !$dayData['is_closed'] ? $dayData['open_time'] : null,
                        'close_time' => $dayData['enabled'] && !$dayData['is_closed'] ? $dayData['close_time'] : null,
                        'is_closed' => !$dayData['enabled'] || $dayData['is_closed'],
                        'enabled' => $dayData['enabled'],
                        'is_24hrs' => $dayData['is_24hrs']
                    ]);
                } else {
                $insertStmt->execute([
                    'store_id' => $storeId,
                    'day_of_week' => $day,
                        'open_time' => $dayData['enabled'] && !$dayData['is_closed'] ? $dayData['open_time'] : null,
                        'close_time' => $dayData['enabled'] && !$dayData['is_closed'] ? $dayData['close_time'] : null,
                        'is_closed' => !$dayData['enabled'] || $dayData['is_closed']
                    ]);
                }
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

