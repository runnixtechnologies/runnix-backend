<?php
// Model/Discount.php
namespace Model;

use Config\Database;
use PDO;

class Discount
{
    private $conn;
    private $table = "discounts";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} (store_id, store_type_id, percentage, start_date, end_date, status, created_at, updated_at)
                    VALUES (:store_id, :store_type_id, :percentage, :start_date, :end_date, 'active', NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'store_id' => $data['store_id'],
                'store_type_id' => $data['store_type_id'] ?? null,
                'percentage' => $data['percentage'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ]);

            if (!$result) {
                error_log("Discount creation failed: " . json_encode($stmt->errorInfo()));
                return false;
            }

            $discountId = $this->conn->lastInsertId();

            // Insert into discount_items
            foreach ($data['items'] as $item) {
                $sqlItem = "INSERT INTO discount_items (discount_id, item_id, item_type, created_at) VALUES (:discount_id, :item_id, :item_type, NOW())";
                $stmtItem = $this->conn->prepare($sqlItem);
                $resultItem = $stmtItem->execute([
                    'discount_id' => $discountId,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type']
                ]);

                if (!$resultItem) {
                    error_log("Discount item creation failed: " . json_encode($stmtItem->errorInfo()));
                    return false;
                }
            }

            return $discountId;
        } catch (\Exception $e) {
            error_log("Discount creation exception: " . $e->getMessage());
            return false;
        }
    }

    public function getAllByStoreId($storeId)
{
    $query = "SELECT * FROM {$this->table} WHERE store_id = :store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
    }
    
    return $results;
}

public function getByItemId($itemId)
{
    $query = "
        SELECT d.* 
        FROM discounts d
        INNER JOIN discount_items di ON di.discount_id = d.id
        WHERE di.item_id = :item_id
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':item_id', $itemId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
    }
    
    return $results;
}

public function getBySideId($sideId)
{
    $query = "
        SELECT d.* 
        FROM discounts d
        INNER JOIN discount_items di ON di.discount_id = d.id
        WHERE di.item_id = :side_id AND di.item_type = 'side'
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':side_id', $sideId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
    }
    
    return $results;
}

public function getByPackId($packId)
{
    $query = "
        SELECT d.* 
        FROM discounts d
        INNER JOIN discount_items di ON di.discount_id = d.id
        WHERE di.item_id = :pack_id AND di.item_type = 'pack'
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':pack_id', $packId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
    }
    
    return $results;
}

public function getAllByStoreIdWithDetails($storeId)
{
    $query = "
        SELECT 
            d.*,
            di.item_id,
            di.item_type,
            CASE 
                WHEN di.item_type = 'food_item' THEN fi.name
                WHEN di.item_type = 'item' THEN i.name
                WHEN di.item_type = 'side' THEN fs.name
                WHEN di.item_type = 'pack' THEN p.name
                ELSE NULL
            END as item_name
        FROM {$this->table} d
        LEFT JOIN discount_items di ON d.id = di.discount_id
        LEFT JOIN food_items fi ON di.item_id = fi.id AND di.item_type = 'food_item'
        LEFT JOIN items i ON di.item_id = i.id AND di.item_type = 'item'
        LEFT JOIN food_sides fs ON di.item_id = fs.id AND di.item_type = 'side'
        LEFT JOIN packages p ON di.item_id = p.id AND di.item_type = 'pack'
        WHERE d.store_id = :store_id
        ORDER BY d.created_at DESC
    ";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
        $result['item_id'] = $result['item_id'] ? (int)$result['item_id'] : null;
    }
    
    return $results;
}

public function getById($id)
{
    $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Convert numeric fields to appropriate types
        $result['percentage'] = (float)$result['percentage'];
        $result['store_id'] = (int)$result['store_id'];
        $result['store_type_id'] = (int)$result['store_type_id'];
    }
    
    return $result;
}


public function update($discountId, $data)
{
    // Update discount only if it matches store and store_type
    $sql = "UPDATE {$this->table} 
            SET percentage = :percentage, 
                start_date = :start_date, 
                end_date = :end_date, 
                updated_at = NOW()
            WHERE id = :id 
              AND store_id = :store_id 
              AND store_type_id = :store_type_id";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        'id' => $discountId,
        'store_id' => $data['store_id'],
        'store_type_id' => $data['store_type_id'],
        'percentage' => $data['percentage'],
        'start_date' => $data['start_date'] ?? null,
        'end_date' => $data['end_date'] ?? null
    ]);

    if ($stmt->rowCount() === 0) {
        // No discount updated — maybe store_id/type mismatch
        return false;
    }

    // Delete old items for that discount (scoped)
    $deleteSql = "DELETE FROM discount_items 
                  WHERE discount_id = :discount_id";
    $deleteStmt = $this->conn->prepare($deleteSql);
    $deleteStmt->execute(['discount_id' => $discountId]);

    // Reinsert updated items
    foreach ($data['items'] as $item) {
        $sqlItem = "INSERT INTO discount_items 
                    (discount_id, item_id, item_type, created_at)
                    VALUES (:discount_id, :item_id, :item_type, NOW())";

        $stmtItem = $this->conn->prepare($sqlItem);
        $stmtItem->execute([
            'discount_id' => $discountId,
            'item_id' => $item['item_id'],
            'item_type' => $item['item_type']
        ]);
    }

    return true;
}


   public function delete($id)
{
    try {
        // Begin transaction
        $this->conn->beginTransaction();

        // Step 1: Delete related discount_items
        $sqlItems = "DELETE FROM discount_items WHERE discount_id = :id";
        $stmtItems = $this->conn->prepare($sqlItems);
        $stmtItems->execute(['id' => $id]);

        // Step 2: Delete the main discount
        $sqlDiscount = "DELETE FROM {$this->table} WHERE id = :id";
        $stmtDiscount = $this->conn->prepare($sqlDiscount);
        $stmtDiscount->execute(['id' => $id]);

        $this->conn->commit();
        return true;
    } catch (\PDOException $e) {
        $this->conn->rollBack();
        error_log("Discount delete failed: " . $e->getMessage());
        return false;
    }
}

}
