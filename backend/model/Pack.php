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
            (store_id, name, price, status, created_at, updated_at) 
            VALUES 
            (:store_id, :name, :price, :status, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => 'active' // Always set to active for new packs
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
                    updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'price' => $data['price']
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
        $sql = "SELECT p.id, p.store_id, p.name, p.price, p.status, p.created_at, p.updated_at,
                       d.percentage, d.start_date, d.end_date, d.status as discount_status,
                       COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                FROM {$this->table} p
                LEFT JOIN item_packs ip ON p.id = ip.pack_id
                LEFT JOIN order_items oi ON ip.item_id = oi.item_id
                LEFT JOIN discount_items di ON p.id = di.item_id AND di.item_type = 'pack'
                LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = p.store_id AND d.status = 'active'
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
            
            // Only include discount fields if there's an active discount with percentage > 0
            if ($result['percentage'] && $result['percentage'] > 0 && $result['discount_status'] === 'active') {
                $result['percentage'] = (float)$result['percentage'];
                $result['start_date'] = $result['start_date'];
                $result['end_date'] = $result['end_date'];
            } else {
                // Remove discount fields if no active discount
                unset($result['percentage']);
                unset($result['start_date']);
                unset($result['end_date']);
            }
            // Always remove the discount_status field as it's internal
            unset($result['discount_status']);
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
            error_log("Pack::getPackById - Searching for pack with ID: $id");
            
            // Get the pack data with discount information
            $sql = "SELECT p.id, p.store_id, p.name, p.price, p.status, p.created_at, p.updated_at,
                           d.percentage, d.start_date, d.end_date, d.status as discount_status
                    FROM {$this->table} p
                    LEFT JOIN discount_items di ON p.id = di.item_id AND di.item_type = 'pack'
                    LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = p.store_id AND d.status = 'active'
                    WHERE p.id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Pack::getPackById - Query result: " . json_encode($result));
            
            if (!$result) {
                error_log("Pack::getPackById - No pack found with ID: $id");
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
            
            // Add total_orders and convert numeric fields
            $result['total_orders'] = (int)$orderCount['total_orders'];
            $result['price'] = (float)$result['price'];
            
            // Only include discount fields if there's an active discount with percentage > 0
            if ($result['percentage'] && $result['percentage'] > 0 && $result['discount_status'] === 'active') {
                $result['percentage'] = (float)$result['percentage'];
                $result['start_date'] = $result['start_date'];
                $result['end_date'] = $result['end_date'];
            } else {
                // Remove discount fields if no active discount
                unset($result['percentage']);
                unset($result['start_date']);
                unset($result['end_date']);
            }
            // Always remove the discount_status field as it's internal
            unset($result['discount_status']);
            
            return $result;
        } catch (PDOException $e) {
            error_log("getPackById error: " . $e->getMessage());
            return false;
        }
    }
}
