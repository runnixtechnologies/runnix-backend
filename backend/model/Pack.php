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
            (store_id, name, price, discount, percentage, discount_start_date, discount_end_date, status, created_at, updated_at) 
            VALUES 
            (:store_id, :name, :price, :discount, :percentage, :discount_start_date, :discount_end_date, :status, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount' => $data['discount'] ?? 0,
            'percentage' => $data['percentage'] ?? 0,
            'discount_start_date' => $data['discount_start_date'] ?? null,
            'discount_end_date' => $data['discount_end_date'] ?? null,
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
                    discount = :discount, 
                    percentage = :percentage, 
                    discount_start_date = :discount_start_date,
                    discount_end_date = :discount_end_date,
                    updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount' => $data['discount'] ?? 0,
            'percentage' => $data['percentage'] ?? 0,
            'discount_start_date' => $data['discount_start_date'] ?? null,
            'discount_end_date' => $data['discount_end_date'] ?? null
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
        $sql = "SELECT p.id, p.store_id, p.name, p.price, p.discount, p.percentage, 
                       p.discount_start_date, p.discount_end_date, p.status, p.created_at, p.updated_at,
                       COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                FROM {$this->table} p
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
            
            // Only include discount fields if there's an actual discount (discount > 0 or percentage > 0)
            if (($result['discount'] && $result['discount'] > 0) || ($result['percentage'] && $result['percentage'] > 0)) {
                $result['discount'] = (float)$result['discount'];
                $result['percentage'] = (float)$result['percentage'];
                $result['discount_start_date'] = $result['discount_start_date'];
                $result['discount_end_date'] = $result['discount_end_date'];
            } else {
                // Remove discount fields if no discount
                unset($result['discount']);
                unset($result['percentage']);
                unset($result['discount_start_date']);
                unset($result['discount_end_date']);
            }
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
            $sql = "SELECT p.id, p.store_id, p.name, p.price, p.discount, p.percentage, 
                           p.discount_start_date, p.discount_end_date, p.status, p.created_at, p.updated_at
                    FROM {$this->table} p
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
            
            // Only include discount fields if there's an actual discount (discount > 0 or percentage > 0)
            if (($result['discount'] && $result['discount'] > 0) || ($result['percentage'] && $result['percentage'] > 0)) {
                $result['discount'] = (float)$result['discount'];
                $result['percentage'] = (float)$result['percentage'];
                $result['discount_start_date'] = $result['discount_start_date'];
                $result['discount_end_date'] = $result['discount_end_date'];
            } else {
                // Remove discount fields if no discount
                unset($result['discount']);
                unset($result['percentage']);
                unset($result['discount_start_date']);
                unset($result['discount_end_date']);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("getPackById error: " . $e->getMessage());
            return false;
        }
    }
}
