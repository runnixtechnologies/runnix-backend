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
        $sql = "INSERT INTO {$this->table} (store_id, store_type_id, percentage, start_date, end_date, status, created_at, updated_at)
                VALUES (:store_id, :store_type_id, :percentage, :start_date, :end_date, 'active', NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'store_id' => $data['store_id'],
            'store_type_id' => $data['store_type_id'],
            'percentage' => $data['percentage'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null
        ]);

        $discountId = $this->conn->lastInsertId();

        // Insert into discount_items
        foreach ($data['items'] as $item) {
            $sqlItem = "INSERT INTO discount_items (discount_id, item_id, item_type, created_at) VALUES (:discount_id, :item_id, :item_type, NOW())";
            $stmtItem = $this->conn->prepare($sqlItem);
            $stmtItem->execute([
                'discount_id' => $discountId,
                'item_id' => $item['item_id'],
                'item_type' => $item['item_type']
            ]);
        }

        return $discountId;
    }

    public function getAllByStoreId($storeId)
{
    $query = "SELECT * FROM {$this->table} WHERE store_id = :store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
