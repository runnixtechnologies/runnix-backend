<?php
namespace Model;

use Config\Database;
use PDO;

class Pack
{
    private $conn;
    private $table = "packages";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
            (store_id, name, price, discount, percentage, status, created_at, updated_at) 
            VALUES 
            (:store_id, :name, :price, :discount, :percentage, :status, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount' => $data['discount'] ?? 0,
            'percentage' => $data['percentage'] ?? 0,
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public function deactivate($packId, $storeId)
{
    $sql = "UPDATE {$this->table} 
            SET status = 'inactive', updated_at = NOW()
            WHERE id = :id AND store_id = :store_id";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        'id' => $packId,
        'store_id' => $storeId
    ]);
}
public function activate($packId, $storeId)
{
    $sql = "UPDATE {$this->table} 
            SET status = 'active', updated_at = NOW()
            WHERE id = :id AND store_id = :store_id";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        'id' => $packId,
        'store_id' => $storeId
    ]);
}

// Activate packs in bulk
public function activateBulk($packIds, $storeId)
{
    $ids = implode(',', array_map('intval', $packIds));
    $sql = "UPDATE {$this->table} SET status = 'active', updated_at = NOW()
            WHERE id IN ($ids) AND store_id = :store_id";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute(['store_id' => $storeId]);
}

// Deactivate packs in bulk
public function deactivateBulk($packIds, $storeId)
{
    $ids = implode(',', array_map('intval', $packIds));
    $sql = "UPDATE {$this->table} SET status = 'inactive', updated_at = NOW()
            WHERE id IN ($ids) AND store_id = :store_id";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute(['store_id' => $storeId]);
}

// Delete packs in bulk
public function deleteBulk($packIds, $storeId)
{
    $ids = implode(',', array_map('intval', $packIds));
    $sql = "DELETE FROM {$this->table}
            WHERE id IN ($ids) AND store_id = :store_id";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute(['store_id' => $storeId]);
}


    public function update($data)
    {
        $sql = "UPDATE {$this->table} 
                SET name = :name, 
                    price = :price, 
                    discount = :discount, 
                    percentage = :percentage, 
                    updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount' => $data['discount'] ?? 0,
            'percentage' => $data['percentage'] ?? 0
        ]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getAll($storeId, $limit = 10, $offset = 0)
{
    try {
        $sql = "SELECT p.id, p.store_id, p.name, p.price, p.discount as discount_price, p.percentage, p.status, p.created_at, p.updated_at,
                       d.id as discount_id,
                       d.start_date as discount_start_date,
                       d.end_date as discount_end_date,
                       COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                FROM {$this->table} p
                LEFT JOIN discount_items di ON p.id = di.item_id AND di.item_type = 'pack'
                LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                    AND NOW() BETWEEN d.start_date AND d.end_date
                LEFT JOIN item_packs ip ON p.id = ip.pack_id
                LEFT JOIN order_items oi ON ip.item_id = oi.item_id
                WHERE p.store_id = :store_id 
                GROUP BY p.id
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['total_orders'] = (int)$result['total_orders'];
            $result['price'] = (float)$result['price'];
            $result['discount_price'] = (float)$result['discount_price'];
            $result['percentage'] = (float)$result['percentage'];
            $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
            $result['discount_start_date'] = $result['discount_start_date'] ? date('Y-m-d', strtotime($result['discount_start_date'])) : null;
            $result['discount_end_date'] = $result['discount_end_date'] ? date('Y-m-d', strtotime($result['discount_end_date'])) : null;
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("getAll packs error: " . $e->getMessage());
        return [];
    }
}


public function countByStore($storeId)
{
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':store_id' => $storeId]);
    return $stmt->fetchColumn();
}

    public function getPackById($id)
    {
        try {
            // Get the pack data
            $sql = "SELECT id, store_id, name, price, discount as discount_price, percentage, status, created_at, updated_at FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            // Get total orders count for all items in this pack
            $orderQuery = "SELECT COUNT(DISTINCT oi.order_id) as total_orders 
                           FROM item_packs ip 
                           JOIN order_items oi ON ip.item_id = oi.item_id 
                           WHERE ip.pack_id = :pack_id";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->execute(['pack_id' => $id]);
            $orderCount = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get active discount information
            $discountQuery = "SELECT d.percentage as discount_percentage, d.start_date, d.end_date, d.id as discount_id,
                                    (p.price - (p.price * d.percentage / 100)) as calculated_discount_price
                             FROM {$this->table} p
                             LEFT JOIN discount_items di ON p.id = di.item_id AND di.item_type = 'pack'
                             LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                                 AND NOW() BETWEEN d.start_date AND d.end_date
                             WHERE p.id = :pack_id";
            $discountStmt = $this->conn->prepare($discountQuery);
            $discountStmt->execute(['pack_id' => $id]);
            $discountInfo = $discountStmt->fetch(PDO::FETCH_ASSOC);
            
            // Add total_orders to the result and convert numeric fields
            $result['total_orders'] = (int)$orderCount['total_orders'];
            $result['price'] = (float)$result['price'];
            $result['discount_price'] = (float)$result['discount_price'];
            $result['percentage'] = (float)$result['percentage'];
            
            // Add discount information
            if ($discountInfo) {
                $result['discount_percentage'] = (float)$discountInfo['discount_percentage'];
                $result['discount_start_date'] = $discountInfo['start_date'];
                $result['discount_end_date'] = $discountInfo['end_date'];
                $result['discount_id'] = (int)$discountInfo['discount_id'];
                $result['calculated_discount_price'] = (float)$discountInfo['calculated_discount_price'];
            } else {
                $result['discount_percentage'] = null;
                $result['discount_start_date'] = null;
                $result['discount_end_date'] = null;
                $result['discount_id'] = null;
                $result['calculated_discount_price'] = null;
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("getPackById error: " . $e->getMessage());
            return false;
        }
    }
}
