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
    // Validate input minimally here for safety
    if (!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '') {
        return ["status" => "error", "message" => "Invalid item name."];
    }
    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        return ["status" => "error", "message" => "Invalid price."];
    }

    try {
        // Optional photo can be NULL or empty string to clear photo
        $photo = isset($data['photo']) ? $data['photo'] : null;

        $sql = "UPDATE {$this->table} SET name = :name, price = :price, photo = :photo, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $itemId,
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':photo' => $photo
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ["status" => "error", "message" => "Item not found or no changes made."];
        }

        http_response_code(200);
        return ["status" => "success", "message" => "Item updated successfully."];
    } catch (PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Update Failed " ];
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



}
