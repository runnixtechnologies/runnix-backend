<?php


namespace Model;

use Config\Database;
use PDO;
use PDOException;

class Item
{
    private $conn;
    private $table = "items";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    
 public function bulkCreateItems($storeId, $categoryId, $items)
{
    try {
        $this->conn->beginTransaction();

        $sql = "INSERT INTO {$this->table} 
                (store_id, category_id, user_id, name, price, photo, status, created_at, updated_at)
                VALUES 
                (:store_id, :category_id, :user_id, :name, :price, :photo, 'active', NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);

        foreach ($items as $item) {
            // Corrected name check
            $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} 
                                               WHERE store_id = :store_id AND name = :name AND deleted = 0");
            $nameCheck->execute([
                'store_id' => $storeId,
                'name' => $item['name']
            ]);

            if ($nameCheck->fetchColumn() > 0) {
                $this->conn->rollBack();
                http_response_code(409); // Conflict
                return ["status" => "error", "message" => "Item '{$item['name']}' already exists in this store. Bulk creation stopped."];
            }

            // Insert item
            $stmt->execute([
                ':store_id' => $storeId,
                ':category_id' => $categoryId,
                ':user_id' => $item['user_id'],
                ':name' => $item['name'],
                ':price' => $item['price'],
                ':photo' => $item['photo'] ?? null
            ]);
        }

        $this->conn->commit();
        http_response_code(201);
        return ["status" => "success", "message" => "Items added successfully."];

    } catch (PDOException $e) {
        $this->conn->rollBack();
        http_response_code(500);
        return ["status" => "error", "message" => "Item Addition Failed: " . $e->getMessage()];
    }
}



   public function createSingleItem($storeId, $categoryId, $userId, $name, $price, $photo = null)
{
    try {
        // Corrected name check
        $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} 
                                           WHERE store_id = :store_id AND name = :name AND deleted = 0");
        $nameCheck->execute([
            'store_id' => $storeId,
            'name' => $name
        ]);

        if ($nameCheck->fetchColumn() > 0) {
            http_response_code(409); // Conflict
            return ['status' => 'error', 'message' => 'Item with this name already exists in this store. Please choose a different name.'];
        }

        $sql = "INSERT INTO {$this->table} 
                (store_id, category_id, user_id, name, price, photo, status, created_at, updated_at)
                VALUES 
                (:store_id, :category_id, :user_id, :name, :price, :photo, 'active', NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':store_id' => $storeId,
            ':category_id' => $categoryId,
            ':user_id' => $userId,
            ':name' => $name,
            ':price' => $price,
            ':photo' => $photo
        ]);

        http_response_code(201);
        $insertedId = $this->conn->lastInsertId();
        return ["status" => "success", "message" => "Item created successfully.", "item_id" => $insertedId];

    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Item Creation Failed: " . $e->getMessage()];
    }
}



   public function updateItem($itemId, $data)
{
    try {
        // Build base SQL
        $sql = "UPDATE {$this->table} SET 
                    name = :name, 
                    price = :price, 
                    updated_at = NOW()";

        // Optional photo field update
        if (!empty($data['photo'])) {
            $sql .= ", photo = :photo";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        // Bind parameters
        $params = [
            ':id' => $itemId,
            ':name' => $data['name'],
            ':price' => $data['price'],
        ];

        if (!empty($data['photo'])) {
            $params[':photo'] = $data['photo'];
        }

        $stmt->execute($params);

       return ["status" => "success", "updated" => $stmt->rowCount()];

    } catch (PDOException $e) {
        // Optional: log the error or expose the message in development mode
        error_log("Update Item Error: " . $e->getMessage());
        return false;
    }
}


public function deleteItem($itemId)
{
    try {
        // Soft delete recommended - you can adapt this if you have a 'deleted' column
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $itemId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ["status" => "error", "message" => "Item not found."];
        }

        http_response_code(200);
        return ["status" => "success", "message" => "Item deleted successfully."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Item Deletion Failed"];
    }
}

public function setItemStatus($itemId, $status)
{
    $validStatuses = ['active', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        return ["status" => "error", "message" => "Invalid status value."];
    }

    try {
        $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $itemId,
            ':status' => $status
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ["status" => "error", "message" => "Item not found or status already set."];
        }

        http_response_code(200);
        return ["status" => "success", "message" => "Item status updated to {$status}."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Item Status Update Failed"];
    }
}

public function isItemOwnedByUser($itemId, $userId)
{
    $sql = "SELECT id FROM {$this->table} WHERE id = :itemId AND user_id = :userId AND deleted = '0'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':itemId' => $itemId, ':userId' => $userId]);
    return $stmt->fetch() !== false;
}

 public function getAllItemsByStoreId($storeId)
{
    $sql = "SELECT * FROM items WHERE store_id = :store_id AND deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':store_id' => $storeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getItemsByStoreIdPaginated($storeId, $limit, $offset)
{
    $sql = "
        SELECT 
            i.*, 
            d.start_date AS discount_start_date, 
            d.end_date AS discount_end_date, 
            d.percentage, 
            (i.price - (i.price * d.percentage / 100)) AS discount_price,
            COUNT(oi.id) AS total_orders
        FROM items i
        LEFT JOIN discount_items di ON i.id = di.item_id
        LEFT JOIN discounts d ON di.discount_id = d.id
        LEFT JOIN order_items oi ON i.id = oi.item_id
        WHERE i.store_id = :store_id
          AND i.deleted = 0
          AND (
              d.id IS NULL OR (d.status = 'active' AND NOW() BETWEEN d.start_date AND d.end_date)
          )
        GROUP BY i.id, d.percentage
        ORDER BY i.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function countItemsByStoreId($storeId)
{
    $sql = "SELECT COUNT(*) as total FROM items WHERE store_id = :store_id AND deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':store_id' => $storeId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) $result['total'];
}

public function getItemsByStoreAndCategory($storeId, $categoryId)
{
    $sql = "SELECT * FROM items 
            WHERE store_id = :store_id AND category_id = :category_id AND deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':store_id' => $storeId,
        ':category_id' => $categoryId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getItemsByStoreAndCategoryPaginated($storeId, $categoryId, $limit, $offset)
{
    $sql = "
        SELECT 
            i.*, 
            d.start_date AS discount_start_date, 
            d.end_date AS discount_end_date, 
            d.percentage, 
            (i.price - (i.price * d.percentage / 100)) AS discount_price,
            COUNT(oi.id) AS total_orders
        FROM items i
        LEFT JOIN discount_items di ON i.id = di.item_id
        LEFT JOIN discounts d ON di.discount_id = d.id
        LEFT JOIN order_items oi ON i.id = oi.item_id
        WHERE i.store_id = :store_id 
          AND i.category_id = :category_id
          AND i.deleted = 0
          AND (
              d.id IS NULL OR (d.status = 'active' AND NOW() BETWEEN d.start_date AND d.end_date)
          )
        GROUP BY i.id, d.percentage
        ORDER BY i.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function countItemsByStoreAndCategory($storeId, $categoryId)
{
    $sql = "SELECT COUNT(*) as total FROM items 
            WHERE store_id = :store_id AND category_id = :category_id AND deleted = 0";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':store_id' => $storeId,
        ':category_id' => $categoryId
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total'];
}


public function updateItemsCategoryBulk($itemIds, $newCategoryId, $storeId)
{
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $query = "UPDATE items 
              SET category_id = ?, updated_at = NOW() 
              WHERE id IN ($placeholders) AND store_id = ? AND deleted = 0";

    $stmt = $this->conn->prepare($query);

    // Merge all bound values: new category, item IDs, then store ID
    $params = array_merge([$newCategoryId], $itemIds, [$storeId]);

    return $stmt->execute($params);
}


public function removeItemsFromCategory($itemIds, $storeId)
{
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $query = "UPDATE items 
              SET category_id = NULL, updated_at = NOW()
              WHERE id IN ($placeholders) AND store_id = ? AND deleted = 0";

    $stmt = $this->conn->prepare($query);
    $params = array_merge($itemIds, [$storeId]);

    return $stmt->execute($params);
}

public function replaceItemsInCategory($itemIds, $categoryId, $storeId)
{
    // Step 1: Clear all existing items in that category for the store
    $clearQuery = "UPDATE items SET category_id = NULL, updated_at = NOW()
                   WHERE category_id = ? AND store_id = ? AND deleted = 0";
    $clearStmt = $this->conn->prepare($clearQuery);
    if (!$clearStmt->execute([$categoryId, $storeId])) {
        return false;
    }

    // Step 2: Assign new items to that category
    if (empty($itemIds)) {
        // If no items to assign, just return true after clearing
        return true;
    }

    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $assignQuery = "UPDATE items SET category_id = ?, updated_at = NOW()
                    WHERE id IN ($placeholders) AND store_id = ? AND deleted = 0";
    $assignStmt = $this->conn->prepare($assignQuery);

    $params = array_merge([$categoryId], $itemIds, [$storeId]);
    return $assignStmt->execute($params);
}

/*
public function deleteItemsBulk($ids)
{
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);

        http_response_code(200);
        return ["status" => "success", "message" => "Items deleted successfully."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Bulk deletion failed."];
    }
}*/

public function deleteItemsBulk($ids)
{
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted = 1, updated_at = NOW() WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);

        http_response_code(200);
        return ["status" => "success", "message" => "Items Deleted successfully."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Bulk deletion failed."];
    }
}


public function updateItemsStatusBulk($ids, $status)
{
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge([$status], $ids));

        http_response_code(200);
        return ["status" => "success", "message" => "Items updated to '$status' status successfully."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Bulk status update failed."];
    }
}


}
