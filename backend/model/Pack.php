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
                SET name = :name, price = :price, updated_at = NOW() 
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
    $sql = "SELECT * FROM {$this->table} WHERE store_id = :store_id LIMIT :limit OFFSET :offset";
    $stmt = $this->conn->prepare($sql);

    $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
