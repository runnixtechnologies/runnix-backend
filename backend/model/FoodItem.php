<?php
// FoodItem.php - Model for Food Items
namespace Model;

use Config\Database;
use PDO;
use PDOException;

class FoodItem
{
    private $conn;
    private $table = "food_items";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    
    // Create Food Item
   public function create($data)
{
    // Validate store_id exists
    $storeCheck = $this->conn->prepare("SELECT COUNT(*) FROM stores WHERE id = :store_id");
    $storeCheck->execute(['store_id' => $data['store_id']]);
    if ($storeCheck->fetchColumn() == 0) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid store_id: no such store found.'];
    }

    // Check if item with the same name exists in the store
    $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id AND name = :name AND deleted = 0");
    $nameCheck->execute([
        'store_id' => $data['store_id'],
        'name' => $data['name']
    ]);
    if ($nameCheck->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        return ['status' => 'error', 'message' => 'Item with this name already exists in this store. Please choose a different name.'];
    }

    // Insert new item
    $sql = "INSERT INTO {$this->table} 
            (store_id, category_id, section_id, user_id, name, price, photo, short_description, max_qty, status, created_at, updated_at)
            VALUES 
            (:store_id, :category_id, :section_id, :user_id, :name, :price, :photo, :short_description, :max_qty, :status, NOW(), NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        'store_id' => $data['store_id'],
        'category_id' => $data['category_id'],
        'section_id' => $data['section_id'] ?? null, // optional
        'user_id' => $data['user_id'],
        'name' => $data['name'],
        'price' => $data['price'],
        'photo' => $data['photo'],
        'short_description' => $data['short_description'],
        'max_qty' => $data['max_qty'],
        'status' => $data['status'] ?? 'active'
    ]);

    http_response_code(201);
    return ['status' => 'success', 'message' => 'Item created successfully.'];
}




    // Read Food Item by id
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update Food Item
    public function update($data)
{
    $query = "UPDATE food_items SET 
                name = :name,
                short_description = :short_description,
                price = :price,
                section_id = :section_id, -- added section_id here
                status = :status,
                updated_at = NOW()";

    if (!empty($data['photo'])) {
        $query .= ", photo = :photo";
    }

    $query .= " WHERE id = :id";
       $sectionId = $data['section_id'] ?? null;

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':short_description', $data['short_description']);
    $stmt->bindParam(':price', $data['price']);

    $stmt->bindParam(':section_id', $sectionId);

    $stmt->bindParam(':status', $data['status']);

    if (!empty($data['photo'])) {
        $stmt->bindParam(':photo', $data['photo']);
    }

    return $stmt->execute();
}


    // Soft Delete Food Item
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    public function itemExists($id) {
    $query = "SELECT COUNT(*) FROM food_items WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetchColumn() > 0;
}

    public function isFoodOwnedByUser($id, $userId)
{
    $sql = "SELECT id FROM {$this->table} WHERE id = :id AND user_id = :userId AND deleted = '0'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':id' => $id, ':userId' => $userId]);
    return $stmt->fetch() !== false;
}
    // Get All Food Items by Store
    public function getAllByStoreId($store_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE store_id = :store_id AND deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $store_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single Food Item by id
public function getByItemId($id)
{
    $sql = "SELECT * FROM {$this->table} WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateFoodItemSide($itemId, $sideId, $extraPrice)
{
    $query = "UPDATE food_item_sides 
              SET extra_price = :extra_price
              WHERE item_id = :item_id AND side_id = :side_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':extra_price', $extraPrice);
    $stmt->bindParam(':item_id', $itemId);
    $stmt->bindParam(':side_id', $sideId);

    return $stmt->execute();
}

 public function createSide($data)
    {
         // Check if item with the same name exists in the store
    $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM food_sides WHERE store_id = :store_id AND name = :name");
    $nameCheck->execute([
        'store_id' => $data['store_id'],
        'name' => $data['name']
    ]);
    if ($nameCheck->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        return ['status' => 'error', 'message' => 'Side Name already exists in this store. Please choose a different name.'];
    }
        $sql = "INSERT INTO food_sides (store_id, name, price, created_at, updated_at) VALUES (:store_id, :name, :price, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function getSidesByStoreId($store_id)
    {
        $sql = "SELECT * FROM food_sides WHERE store_id = :store_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $store_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateSide($data)
    {
        $sql = "UPDATE food_sides SET name=:name, price=:price, updated_at=NOW() WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteSide($id)
    {
        $sql = "DELETE FROM food_sides WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // Food Item Side Mapping
    public function mapSideToFoodItem($data)
    {
        $sql = "INSERT INTO food_item_sides (food_item_id, food_side_id) VALUES (:food_item_id, :food_side_id)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function getSidesForFoodItem($food_item_id)
    {
        $sql = "SELECT fs.* FROM food_item_sides fis JOIN food_sides fs ON fis.food_side_id = fs.id WHERE fis.food_item_id = :food_item_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['food_item_id' => $food_item_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Food Sections CRUD
    public function createSection($data)
    {
        $sql = "INSERT INTO food_sections (store_id, name, created_at, updated_at) VALUES (:store_id, :name, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function getSectionsByStoreId($store_id)
    {
        $sql = "SELECT * FROM food_sections WHERE store_id = :store_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $store_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateSection($data)
    {
        $sql = "UPDATE food_sections SET name=:name, updated_at=NOW() WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteSection($id)
    {
        $sql = "DELETE FROM food_sections WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    // CREATE Food Side
public function createFoodSide($data)
{
    // Check for existing name
    $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM food_sides WHERE store_id = :store_id AND name = :name");
    $nameCheck->execute([
        'store_id' => $data['store_id'],
        'name' => $data['name']
    ]);
    if ($nameCheck->fetchColumn() > 0) {
         http_response_code(400);
        return ['status' => 'error', 'message' => 'Side Name already exists in this store. Please choose a different name.'];
    }

    // Otherwise insert
    $query = "INSERT INTO food_sides (store_id, name, price) VALUES (:store_id, :name, :price)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $data['store_id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->execute();

    return ['status' => 'success', 'message' => 'Food Side created successfully.'];
}


public function getFoodSideById($id)
{
    $query = "SELECT * FROM food_sides WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// READ All Sides by Store

public function getAllFoodSidesByStoreId($store_id, $limit = 10, $offset = 0)
{
    $query = "SELECT * FROM food_sides WHERE store_id = :store_id LIMIT :limit OFFSET :offset";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// UPDATE Food Side
public function updateFoodSide($data)
{
    $query = "UPDATE food_sides SET name = :name, price = :price WHERE id = :id AND store_id =:store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':id', $data['id']);
     $stmt->bindParam(':store_id', $data['store_id']);
    return $stmt->execute();
}



// DELETE Food Side
public function deleteFoodSide($id)
{
    $query = "DELETE FROM food_sides WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id); 
    return $stmt->execute();
}



public function updateFoodSideStatus($id, $status)
{
    $sql = "UPDATE food_sides SET status = :status WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}


public function bulkDeleteFoodSides($ids)
{
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "DELETE FROM food_sides WHERE id IN ($placeholders)";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute($ids) ? $stmt->rowCount() : 0;
}
public function bulkUpdateFoodSideStatus($ids, $status)
{
    if (empty($ids)) return false;

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE food_sides SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";

    $stmt = $this->conn->prepare($sql);

    $params = array_merge([$status], $ids);
    return $stmt->execute($params);
}


// CREATE Food Item Side Mapping with Extra Price
public function createFoodItemSide($data)
{
    $query = "INSERT INTO food_item_sides (item_id, side_id, extra_price) 
              VALUES (:item_id, :side_id, :extra_price)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':item_id', $data['item_id']);
    $stmt->bindParam(':side_id', $data['side_id']);
    $stmt->bindParam(':extra_price', $data['extra_price']);
    return $stmt->execute();
}

// READ All Sides for a Food Item (with extra_price included)
public function getAllSidesForFoodItem($itemId)
{
    $query = "SELECT fs.*, fis.extra_price 
              FROM food_sides fs
              INNER JOIN food_item_sides fis ON fs.id = fis.side_id
              WHERE fis.item_id = :item_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':item_id', $itemId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// DELETE Food Item Side Mapping
public function deleteFoodItemSide($itemId, $sideId)
{
    $query = "DELETE FROM food_item_sides 
              WHERE item_id = :item_id AND side_id = :side_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':item_id', $itemId);
    $stmt->bindParam(':side_id', $sideId);
    return $stmt->execute();
}
// CREATE Food Section (ensure unique section name per store)
public function createFoodSection($data)
{
    try {
        // Begin transaction
        $this->conn->beginTransaction();

        // Set defaults if not provided
        $maxQuantity = isset($data['max_quantity']) ? $data['max_quantity'] : null;
        $required = isset($data['required']) ? $data['required'] : 0;

        // Insert new section
        $query = "INSERT INTO food_sections (store_id, section_name, max_quantity, required) 
                  VALUES (:store_id, :section_name, :max_quantity, :required)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':store_id', $data['store_id']);
        $stmt->bindParam(':section_name', $data['section_name']);
        $stmt->bindParam(':max_quantity', $maxQuantity);
        $stmt->bindParam(':required', $required);
        $stmt->execute();

        // Get the new section ID
        $sectionId = $this->conn->lastInsertId();

        // Attach sides if provided and valid
        if (!empty($data['side_ids']) && is_array($data['side_ids'])) {
            $sideQuery = "INSERT INTO food_section_sides (section_id, side_id) VALUES (:section_id, :side_id)";
            $sideStmt = $this->conn->prepare($sideQuery);

            foreach ($data['side_ids'] as $sideId) {
                // Use bindValue inside the loop to avoid PDO binding issues
                $sideStmt->bindValue(':section_id', $sectionId);
                $sideStmt->bindValue(':side_id', $sideId);
                $sideStmt->execute();
            }
        }

        // Commit transaction
        $this->conn->commit();
        return true;

    } catch (\PDOException $e) {
        // Rollback on error
        $this->conn->rollBack();
        throw new \Exception("Error creating food section: " . $e->getMessage());
    }
}


// READ All Sections by Store
public function getAllFoodSectionsByStoreId($storeId)
{
    // First, get all sections for this store
    $query = "SELECT * FROM food_sections WHERE store_id = :store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each section, get attached sides
    foreach ($sections as &$section) {
        $sideQuery = "SELECT fs.side_id, s.name 
                      FROM food_section_sides fs
                      JOIN food_sides s ON fs.side_id = s.id
                      WHERE fs.section_id = :section_id";
        $sideStmt = $this->conn->prepare($sideQuery);
        $sideStmt->bindParam(':section_id', $section['id']);
        $sideStmt->execute();
        $sides = $sideStmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach sides to this section
        $section['sides'] = $sides;
    }

    return $sections;
}


// GET Single Section by ID
public function getFoodSectionById($id)
{
    // Get the section
    $query = "SELECT * FROM food_sections WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($section) {
        // Get attached sides
        $sideQuery = "SELECT fs.side_id, s.name 
                      FROM food_section_sides fs
                      JOIN food_sides s ON fs.side_id = s.id
                      WHERE fs.section_id = :id";
        $sideStmt = $this->conn->prepare($sideQuery);
        $sideStmt->bindParam(':id', $id);
        $sideStmt->execute();
        $sides = $sideStmt->fetchAll(PDO::FETCH_ASSOC);

        $section['sides'] = $sides;
    }

    return $section ?: null;
}


// UPDATE Food Section
public function updateFoodSection($data)
{
    try {
        // Begin transaction
        $this->conn->beginTransaction();

        // Set defaults if not provided
        $maxQuantity = isset($data['max_quantity']) ? $data['max_quantity'] : null;
        $required = isset($data['required']) ? $data['required'] : 0;

        // Update the section details
        $query = "UPDATE food_sections 
                  SET section_name = :section_name, max_quantity = :max_quantity, required = :required
                  WHERE id = :section_id AND store_id = :store_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':section_name', $data['section_name']);
        $stmt->bindParam(':max_quantity', $maxQuantity);
        $stmt->bindParam(':required', $required);
        $stmt->bindParam(':section_id', $data['section_id']);
        $stmt->bindParam(':store_id', $data['store_id']);
        $stmt->execute();

        // Handle sides if provided
        if (isset($data['side_ids']) && is_array($data['side_ids'])) {
            // First, delete all existing sides for this section
            $deleteQuery = "DELETE FROM food_section_sides WHERE section_id = :section_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':section_id', $data['section_id']);
            $deleteStmt->execute();

            // Insert the new sides
            if (!empty($data['side_ids'])) {
                $insertQuery = "INSERT INTO food_section_sides (section_id, side_id) VALUES (:section_id, :side_id)";
                $insertStmt = $this->conn->prepare($insertQuery);

                foreach ($data['side_ids'] as $sideId) {
                    $insertStmt->bindValue(':section_id', $data['section_id']);
                    $insertStmt->bindValue(':side_id', $sideId);
                    $insertStmt->execute();
                }
            }
        }

        // Commit transaction
        $this->conn->commit();
        return true;

    } catch (PDOException $e) {
        // Rollback on error
        $this->conn->rollBack();
        throw new \Exception("Error updating food section: " . $e->getMessage());
    }
}


// DELETE Food Section
public function deleteFoodSection($id)
{
    $query = "DELETE FROM food_sections WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}


public function getItemsByStoreAndCategory($storeId, $categoryId)
{
    $sql = "SELECT * FROM food_items 
            WHERE store_id = :store_id AND category_id = :category_id AND deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':store_id' => $storeId,
        ':category_id' => $categoryId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function updateItemsCategoryBulk($itemIds, $newCategoryId, $storeId)
{
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $query = "UPDATE food_items 
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

    $query = "UPDATE food_items 
              SET category_id = NULL, updated_at = NOW()
              WHERE id IN ($placeholders) AND store_id = ? AND deleted = 0";

    $stmt = $this->conn->prepare($query);
    $params = array_merge($itemIds, [$storeId]);

    return $stmt->execute($params);
}


public function getItemsByStoreAndCategoryPaginated($storeId, $categoryId, $limit, $offset)
{
    $sql = "SELECT * FROM food_items 
            WHERE store_id = :store_id AND category_id = :category_id AND deleted = 0
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countItemsByStoreAndCategory($storeId, $categoryId)
{
    $sql = "SELECT COUNT(*) as total FROM food_items 
            WHERE store_id = :store_id AND category_id = :category_id AND deleted = 0";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':store_id' => $storeId,
        ':category_id' => $categoryId
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total'];
}


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


