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
        error_log("=== DISCOUNT MODEL CREATE ===");
        error_log("Create data: " . json_encode($data));
        
        try {
            $sql = "INSERT INTO {$this->table} (store_id, store_type_id, percentage, start_date, end_date, status, created_at, updated_at)
                    VALUES (:store_id, :store_type_id, :percentage, :start_date, :end_date, 'active', NOW(), NOW())";

            error_log("SQL Query: " . $sql);
            error_log("Parameters: " . json_encode([
                'store_id' => $data['store_id'],
                'store_type_id' => $data['store_type_id'] ?? null,
                'percentage' => $data['percentage'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ]));

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'store_id' => $data['store_id'],
                'store_type_id' => $data['store_type_id'] ?? null,
                'percentage' => $data['percentage'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ]);

            error_log("Execute result: " . ($result ? 'SUCCESS' : 'FAILED'));
            if (!$result) {
                error_log("Discount creation failed: " . json_encode($stmt->errorInfo()));
                return false;
            }

            $discountId = $this->conn->lastInsertId();
            error_log("Created discount ID: " . $discountId);

            // Insert into discount_items
            error_log("=== INSERTING DISCOUNT ITEMS ===");
            error_log("Items to insert: " . json_encode($data['items']));
            foreach ($data['items'] as $index => $item) {
                $sqlItem = "INSERT INTO discount_items (discount_id, item_id, item_type, created_at) VALUES (:discount_id, :item_id, :item_type, NOW())";
                error_log("Inserting item " . ($index + 1) . ": " . json_encode($item));
                $stmtItem = $this->conn->prepare($sqlItem);
                $resultItem = $stmtItem->execute([
                    'discount_id' => $discountId,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type']
                ]);

                error_log("Item " . ($index + 1) . " insert result: " . ($resultItem ? 'SUCCESS' : 'FAILED'));
                if (!$resultItem) {
                    error_log("Discount item creation failed: " . json_encode($stmtItem->errorInfo()));
                    return false;
                }
            }
            
            error_log("=== DISCOUNT CREATION COMPLETED SUCCESSFULLY ===");

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
                WHEN di.item_type = 'food_section_item' THEN fsi.name
                ELSE NULL
            END as item_name
        FROM {$this->table} d
        LEFT JOIN discount_items di ON d.id = di.discount_id
        LEFT JOIN food_items fi ON di.item_id = fi.id AND di.item_type = 'food_item'
        LEFT JOIN items i ON di.item_id = i.id AND di.item_type = 'item'
        LEFT JOIN food_sides fs ON di.item_id = fs.id AND di.item_type = 'side'
        LEFT JOIN packages p ON di.item_id = p.id AND di.item_type = 'pack'
        LEFT JOIN food_section_items fsi ON di.item_id = fsi.id AND di.item_type = 'food_section_item'
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
    error_log("=== DISCOUNT MODEL UPDATE ===");
    error_log("Discount ID: " . $discountId);
    error_log("Update data: " . json_encode($data));
    
    // Update discount with more flexible WHERE clause
    $sql = "UPDATE {$this->table} 
            SET percentage = :percentage, 
                start_date = :start_date, 
                end_date = :end_date, 
                updated_at = NOW()
            WHERE id = :id 
              AND store_id = :store_id";

    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode([
        'id' => $discountId,
        'store_id' => $data['store_id'],
        'percentage' => $data['percentage'],
        'start_date' => $data['start_date'] ?? null,
        'end_date' => $data['end_date'] ?? null
    ]));

    $stmt = $this->conn->prepare($sql);
    $result = $stmt->execute([
        'id' => $discountId,
        'store_id' => $data['store_id'],
        'percentage' => $data['percentage'],
        'start_date' => $data['start_date'] ?? null,
        'end_date' => $data['end_date'] ?? null
    ]);
    
    $rowCount = $stmt->rowCount();
    error_log("Execute result: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("Rows affected: " . $rowCount);

    if ($rowCount === 0) {
        error_log("=== NO ROWS UPDATED ===");
        // No discount updated — maybe store_id mismatch
        return false;
    }
    
    error_log("=== DISCOUNT UPDATED SUCCESSFULLY ===");

    // Delete old items for that discount (scoped)
    error_log("=== UPDATING DISCOUNT ITEMS ===");
    $deleteSql = "DELETE FROM discount_items 
                  WHERE discount_id = :discount_id";
    error_log("Delete SQL: " . $deleteSql);
    $deleteStmt = $this->conn->prepare($deleteSql);
    $deleteResult = $deleteStmt->execute(['discount_id' => $discountId]);
    $deletedRows = $deleteStmt->rowCount();
    error_log("Delete result: " . ($deleteResult ? 'SUCCESS' : 'FAILED'));
    error_log("Deleted rows: " . $deletedRows);

    // Reinsert updated items
    error_log("Items to insert: " . json_encode($data['items']));
    foreach ($data['items'] as $index => $item) {
        $sqlItem = "INSERT INTO discount_items 
                    (discount_id, item_id, item_type, created_at)
                    VALUES (:discount_id, :item_id, :item_type, NOW())";

        error_log("Inserting item " . ($index + 1) . ": " . json_encode($item));
        $stmtItem = $this->conn->prepare($sqlItem);
        $insertResult = $stmtItem->execute([
            'discount_id' => $discountId,
            'item_id' => $item['item_id'],
            'item_type' => $item['item_type']
        ]);
        error_log("Insert result for item " . ($index + 1) . ": " . ($insertResult ? 'SUCCESS' : 'FAILED'));
    }

    error_log("=== DISCOUNT UPDATE COMPLETED SUCCESSFULLY ===");
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
        $resultItems = $stmtItems->execute(['id' => $id]);
        
        if (!$resultItems) {
            error_log("Failed to delete discount_items: " . json_encode($stmtItems->errorInfo()));
            $this->conn->rollBack();
            return false;
        }

        // Step 2: Delete the main discount
        $sqlDiscount = "DELETE FROM {$this->table} WHERE id = :id";
        $stmtDiscount = $this->conn->prepare($sqlDiscount);
        $resultDiscount = $stmtDiscount->execute(['id' => $id]);
        
        if (!$resultDiscount) {
            error_log("Failed to delete discount: " . json_encode($stmtDiscount->errorInfo()));
            $this->conn->rollBack();
            return false;
        }

        // Check if any rows were actually deleted
        if ($stmtDiscount->rowCount() === 0) {
            error_log("No discount found with ID: " . $id);
            $this->conn->rollBack();
            return false;
        }

        $this->conn->commit();
        return true;
    } catch (\PDOException $e) {
        $this->conn->rollBack();
        error_log("Discount delete failed: " . $e->getMessage());
        return false;
    }
}

}
