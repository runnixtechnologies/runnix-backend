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

// Create Food Item with Sides, Packs, and Sections
public function createWithOptions($data)
{
    try {
        // Begin transaction
        $this->conn->beginTransaction();

        // Validate store_id exists
        $storeCheck = $this->conn->prepare("SELECT COUNT(*) FROM stores WHERE id = :store_id");
        $storeCheck->execute(['store_id' => $data['store_id']]);
        if ($storeCheck->fetchColumn() == 0) {
            throw new \Exception('Invalid store_id: no such store found.');
        }

        // Check if item with the same name exists in the store
        $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id AND name = :name AND deleted = 0");
        $nameCheck->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name']
        ]);
        if ($nameCheck->fetchColumn() > 0) {
            throw new \Exception('Item with this name already exists in this store. Please choose a different name.');
        }

        // Insert new food item
        $sql = "INSERT INTO {$this->table} 
                (store_id, category_id, section_id, user_id, name, price, photo, short_description, max_qty, status, created_at, updated_at)
                VALUES 
                (:store_id, :category_id, :section_id, :user_id, :name, :price, :photo, :short_description, :max_qty, :status, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'store_id' => $data['store_id'],
            'category_id' => $data['category_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'photo' => $data['photo'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'max_qty' => $data['max_qty'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);

        $foodItemId = $this->conn->lastInsertId();

        // Handle sides if provided
        if (isset($data['sides']) && is_array($data['sides']) && !empty($data['sides']['items'])) {
            $this->createFoodItemSidesWithConfig($foodItemId, $data['sides']);
        }

        // Handle packs if provided
        if (isset($data['packs']) && is_array($data['packs']) && !empty($data['packs']['items'])) {
            $this->createFoodItemPacksWithConfig($foodItemId, $data['packs']);
        }

        // Handle sections if provided
        if (isset($data['sections']) && is_array($data['sections']) && !empty($data['sections']['items'])) {
            $this->createFoodItemSectionsWithConfig($foodItemId, $data['sections']);
        }

        // Commit transaction
        $this->conn->commit();

        // Get the created food item with all its options
        $createdItem = $this->getFoodItemWithOptions($foodItemId);

        return [
            'status' => 'success', 
            'message' => 'Food item created successfully with all options',
            'data' => $createdItem
        ];

    } catch (\Exception $e) {
        // Rollback on error
        $this->conn->rollBack();
        error_log("createWithOptions error: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}



// Helper method to get food item with all options
private function getFoodItemWithOptions($foodItemId)
{
    // Get basic food item info
    $itemSql = "SELECT * FROM {$this->table} WHERE id = :id AND deleted = 0";
    $itemStmt = $this->conn->prepare($itemSql);
    $itemStmt->execute(['id' => $foodItemId]);
    $foodItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$foodItem) {
        return null;
    }

    // Get sides configuration and items
    $sidesConfigSql = "SELECT required, max_quantity FROM food_item_sides_config WHERE item_id = :item_id";
    $sidesConfigStmt = $this->conn->prepare($sidesConfigSql);
    $sidesConfigStmt->execute(['item_id' => $foodItemId]);
    $sidesConfig = $sidesConfigStmt->fetch(PDO::FETCH_ASSOC);

    $sidesSql = "SELECT fs.*, fis.extra_price 
                  FROM food_sides fs 
                  INNER JOIN food_item_sides fis ON fs.id = fis.side_id 
                  WHERE fis.item_id = :item_id";
    $sidesStmt = $this->conn->prepare($sidesSql);
    $sidesStmt->execute(['item_id' => $foodItemId]);
    $sides = $sidesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get packs configuration and items
    $packsConfigSql = "SELECT required, max_quantity FROM food_item_packs_config WHERE item_id = :item_id";
    $packsConfigStmt = $this->conn->prepare($packsConfigSql);
    $packsConfigStmt->execute(['item_id' => $foodItemId]);
    $packsConfig = $packsConfigStmt->fetch(PDO::FETCH_ASSOC);

    $packsSql = "SELECT p.*, fip.extra_price 
                  FROM packages p 
                  INNER JOIN food_item_packs fip ON p.id = fip.pack_id 
                  WHERE fip.item_id = :item_id";
    $packsStmt = $this->conn->prepare($packsSql);
    $packsStmt->execute(['item_id' => $foodItemId]);
    $packs = $packsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sections configuration and items
    $sectionsConfigSql = "SELECT required, max_quantity FROM food_item_sections_config WHERE item_id = :item_id";
    $sectionsConfigStmt = $this->conn->prepare($sectionsConfigSql);
    $sectionsConfigStmt->execute(['item_id' => $foodItemId]);
    $sectionsConfig = $sectionsConfigStmt->fetch(PDO::FETCH_ASSOC);

    $sectionsSql = "SELECT fs.* 
                    FROM food_sections fs 
                    INNER JOIN food_item_sections fis ON fs.id = fis.section_id 
                    WHERE fis.item_id = :item_id";
    $sectionsStmt = $this->conn->prepare($sectionsSql);
    $sectionsStmt->execute(['item_id' => $foodItemId]);
    $sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build the complete response
    $foodItem['sides'] = [
        'required' => $sidesConfig ? (bool)$sidesConfig['required'] : false,
        'max_quantity' => $sidesConfig ? (int)$sidesConfig['max_quantity'] : 0,
        'items' => $sides
    ];

    $foodItem['packs'] = [
        'required' => $packsConfig ? (bool)$packsConfig['required'] : false,
        'max_quantity' => $packsConfig ? (int)$packsConfig['max_quantity'] : 0,
        'items' => $packs
    ];

    $foodItem['sections'] = [
        'required' => $sectionsConfig ? (bool)$sectionsConfig['required'] : false,
        'max_quantity' => $sectionsConfig ? (int)$sectionsConfig['max_quantity'] : 0,
        'items' => $sections
    ];

    return $foodItem;
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['price'] = (float)$result['price'];
            $result['max_qty'] = (int)$result['max_qty'];
        }
        
        return $results;
    }

    // Get a single Food Item by id
public function getByItemId($id)
{
    $sql = "SELECT * FROM {$this->table} WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Convert numeric fields to appropriate types
        $result['price'] = (float)$result['price'];
        $result['max_qty'] = (int)$result['max_qty'];
    }
    
    return $result;
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['price'] = (float)$result['price'];
            $result['discount'] = (float)$result['discount'];
            $result['percentage'] = (float)$result['percentage'];
        }
        
        return $results;
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['price'] = (float)$result['price'];
            $result['discount'] = (float)$result['discount'];
            $result['percentage'] = (float)$result['percentage'];
        }
        
        return $results;
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

    // Set default values for optional fields
    $discount = $data['discount'] ?? 0;
    $percentage = $data['percentage'] ?? 0;
    $status = $data['status'] ?? 'active';

    // Otherwise insert
    $query = "INSERT INTO food_sides (store_id, name, price, discount, percentage, status, created_at, updated_at) 
              VALUES (:store_id, :name, :price, :discount, :percentage, :status, NOW(), NOW())";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $data['store_id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':discount', $discount);
    $stmt->bindParam(':percentage', $percentage);
    $stmt->bindParam(':status', $status);
    $stmt->execute();

    return ['status' => 'success', 'message' => 'Food Side created successfully.'];
}


public function getFoodSideById($id)
{
    try {
        error_log("getFoodSideById model: Looking for ID: $id");
        
        // Get the food side data with discount information
        $query = "SELECT fs.id, fs.store_id, fs.name, fs.price, fs.discount as discount_price, fs.percentage, fs.status, fs.created_at, fs.updated_at,
                         d.percentage as discount_percentage,
                         d.start_date as discount_start_date,
                         d.end_date as discount_end_date,
                         d.id as discount_id,
                         (fs.price - (fs.price * d.percentage / 100)) as calculated_discount_price
                  FROM food_sides fs
                  LEFT JOIN discount_items di ON fs.id = di.item_id AND di.item_type = 'side'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                      AND NOW() BETWEEN d.start_date AND d.end_date
                  WHERE fs.id = :id";
        error_log("getFoodSideById model: Executing query: $query with ID: $id");
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("getFoodSideById model: Query executed. Row count: " . $stmt->rowCount());
        error_log("getFoodSideById model: Raw result: " . var_export($result, true));
        
        if (!$result) {
            error_log("getFoodSideById model: No food side found with ID: $id");
            
            // Let's also check if the food_sides table exists and has data
            $checkQuery = "SELECT COUNT(*) as total FROM food_sides";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $totalCount = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("getFoodSideById model: Total food sides in table: " . $totalCount['total']);
            
            return false;
        }
        
        error_log("getFoodSideById model: Found food side: " . json_encode($result));
        
        // Set total_orders to 0 and convert numeric fields
        $result['total_orders'] = 0;
        $result['price'] = (float)$result['price'];
        $result['discount_price'] = (float)$result['discount_price'];
        $result['percentage'] = (float)$result['percentage'];
        $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
        $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
        $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
        
        error_log("getFoodSideById model: Final result: " . json_encode($result));
        
        return $result;
    } catch (PDOException $e) {
        error_log("getFoodSideById error: " . $e->getMessage());
        error_log("getFoodSideById error trace: " . $e->getTraceAsString());
        return false;
    }
}

// READ All Sides by Store
public function getAllFoodSidesByStoreId($store_id, $limit = 10, $offset = 0)
{
    try {
        $query = "SELECT fs.id, fs.store_id, fs.name, fs.price, fs.discount as discount_price, fs.percentage, fs.status, fs.created_at, fs.updated_at,
                         d.id as discount_id,
                         d.store_id as discount_store_id,
                         d.store_type_id as discount_store_type_id,
                         d.percentage as discount_percentage,
                         d.start_date as discount_start_date,
                         d.end_date as discount_end_date,
                         d.status as discount_status,
                         d.created_at as discount_created_at,
                         d.updated_at as discount_updated_at,
                         (fs.price - (fs.price * d.percentage / 100)) as calculated_discount_price,
                         CASE 
                             WHEN d.id IS NOT NULL AND d.status = 'active' 
                                  AND NOW() BETWEEN d.start_date AND d.end_date 
                             THEN true 
                             ELSE false 
                         END as has_active_discount
                  FROM food_sides fs
                  LEFT JOIN discount_items di ON fs.id = di.item_id AND di.item_type = 'side'
                  LEFT JOIN discounts d ON di.discount_id = d.id
                  WHERE fs.store_id = :store_id 
                  ORDER BY fs.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['total_orders'] = 0;
            $result['price'] = (float)$result['price'];
            $result['discount_price'] = (float)$result['discount_price'];
            $result['percentage'] = (float)$result['percentage'];
            
            // Convert discount fields
            $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
            $result['discount_store_id'] = $result['discount_store_id'] ? (int)$result['discount_store_id'] : null;
            $result['discount_store_type_id'] = $result['discount_store_type_id'] ? (int)$result['discount_store_type_id'] : null;
            $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
            $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
            $result['has_active_discount'] = (bool)$result['has_active_discount'];
            
            // Create a discount object for better structure
            if ($result['discount_id']) {
                $result['discount'] = [
                    'id' => $result['discount_id'],
                    'store_id' => $result['discount_store_id'],
                    'store_type_id' => $result['discount_store_type_id'],
                    'percentage' => $result['discount_percentage'],
                    'start_date' => $result['discount_start_date'],
                    'end_date' => $result['discount_end_date'],
                    'status' => $result['discount_status'],
                    'created_at' => $result['discount_created_at'],
                    'updated_at' => $result['discount_updated_at'],
                    'is_active' => $result['has_active_discount']
                ];
            } else {
                $result['discount'] = null;
            }
            
            // Remove individual discount fields to avoid duplication
            unset($result['discount_id'], $result['discount_store_id'], $result['discount_store_type_id'], 
                  $result['discount_percentage'], $result['discount_start_date'], $result['discount_end_date'], 
                  $result['discount_status'], $result['discount_created_at'], $result['discount_updated_at'], 
                  $result['has_active_discount']);
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("getAllFoodSidesByStoreId error: " . $e->getMessage());
        return [];
    }
}

// Get total count of food sides by store ID
public function getFoodSidesCountByStoreId($store_id)
{
    try {
        $query = "SELECT COUNT(*) FROM food_sides WHERE store_id = :store_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("getFoodSidesCountByStoreId error: " . $e->getMessage());
        return 0;
    }
}

// UPDATE Food Side
public function updateFoodSide($data)
{
    // Set default values for optional fields
    $discount = $data['discount'] ?? 0;
    $percentage = $data['percentage'] ?? 0;
    $status = $data['status'] ?? 'active';

    $query = "UPDATE food_sides SET 
              name = :name, 
              price = :price, 
              discount = :discount, 
              percentage = :percentage, 
              status = :status,
              updated_at = NOW() 
              WHERE id = :id AND store_id = :store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':discount', $discount);
    $stmt->bindParam(':percentage', $percentage);
    $stmt->bindParam(':status', $status);
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
    try {
        if (empty($ids) || !is_array($ids)) {
            error_log("bulkDeleteFoodSides: Invalid input - empty or not array");
            return false;
        }

        // Log the IDs being processed
        error_log("bulkDeleteFoodSides: Processing IDs: " . implode(',', $ids));

        // Begin transaction
        $this->conn->beginTransaction();

        // First, check if all IDs exist and are valid
        $checkPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $checkQuery = "SELECT COUNT(*) FROM food_sides WHERE id IN ($checkPlaceholders)";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute($ids);
        
        $existingCount = $checkStmt->fetchColumn();
        error_log("bulkDeleteFoodSides: Found $existingCount existing food sides out of " . count($ids) . " requested");
        
        if ($existingCount != count($ids)) {
            error_log("bulkDeleteFoodSides: Only $existingCount out of " . count($ids) . " IDs exist");
            $this->conn->rollBack();
            return false;
        }

        // Check for foreign key constraints (food_item_sides table)
        $fkPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $fkCheckQuery = "SELECT COUNT(*) FROM food_item_sides WHERE side_id IN ($fkPlaceholders)";
        $fkCheckStmt = $this->conn->prepare($fkCheckQuery);
        $fkCheckStmt->execute($ids);
        $fkCount = $fkCheckStmt->fetchColumn();
        
        error_log("bulkDeleteFoodSides: Found $fkCount food sides linked to food items");
        
        if ($fkCount > 0) {
            error_log("bulkDeleteFoodSides: Cannot delete - $fkCount food sides are linked to food items");
            $this->conn->rollBack();
            return false;
        }

        // Delete the food sides
        $deletePlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $deleteQuery = "DELETE FROM food_sides WHERE id IN ($deletePlaceholders)";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->execute($ids);
        
        $deletedCount = $deleteStmt->rowCount();
        error_log("bulkDeleteFoodSides: Attempted to delete, rowCount returned: $deletedCount");
        
        if ($deletedCount == 0) {
            error_log("bulkDeleteFoodSides: No rows were deleted");
            $this->conn->rollBack();
            return false;
        }
        
        // Commit transaction
        $this->conn->commit();
        
        error_log("bulkDeleteFoodSides: Successfully deleted $deletedCount food sides");
        return $deletedCount;
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        error_log("Bulk delete food sides error: " . $e->getMessage());
        error_log("Bulk delete food sides error trace: " . $e->getTraceAsString());
        return false;
    }
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

public function getFoodSidesByIds($ids)
{
    try {
        if (empty($ids) || !is_array($ids)) {
            error_log("getFoodSidesByIds: Invalid input - empty or not array");
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id, store_id FROM food_sides WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("getFoodSidesByIds: Found " . count($result) . " food sides for IDs: " . implode(',', $ids));
        return $result;
        
    } catch (PDOException $e) {
        error_log("getFoodSidesByIds error: " . $e->getMessage());
        return [];
    }
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
    $query = "SELECT fs.id, fs.store_id, fs.name, fs.price, fs.discount as discount_price, fs.percentage, fs.status, fs.created_at, fs.updated_at, fis.extra_price 
              FROM food_sides fs
              INNER JOIN food_item_sides fis ON fs.id = fis.side_id
              WHERE fis.item_id = :item_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':item_id', $itemId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['price'] = (float)$result['price'];
        $result['discount_price'] = (float)$result['discount_price'];
        $result['percentage'] = (float)$result['percentage'];
        $result['extra_price'] = (float)$result['extra_price'];
    }
    
    return $results;
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
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['price'] = (float)$result['price'];
        $result['max_qty'] = (int)$result['max_qty'];
    }
    
    return $results;
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

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric fields to appropriate types for each result
    foreach ($results as &$result) {
        $result['price'] = (float)$result['price'];
        $result['max_qty'] = (int)$result['max_qty'];
    }
    
    return $results;
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

// Helper method to create food item sides with configuration
private function createFoodItemSidesWithConfig($foodItemId, $sidesData)
{
    // First, create the food item sides configuration
    $configSql = "INSERT INTO food_item_sides_config (item_id, required, max_quantity) 
                  VALUES (:item_id, :required, :max_quantity)";
    $configStmt = $this->conn->prepare($configSql);
    $configStmt->execute([
        'item_id' => $foodItemId,
        'required' => $sidesData['required'] ?? false,
        'max_quantity' => $sidesData['max_quantity'] ?? 0
    ]);

    // Then, create the side relationships
    if (!empty($sidesData['items']) && is_array($sidesData['items'])) {
        $sideSql = "INSERT INTO food_item_sides (item_id, side_id, extra_price) VALUES (:item_id, :side_id, :extra_price)";
        $sideStmt = $this->conn->prepare($sideSql);

        foreach ($sidesData['items'] as $sideItem) {
            $sideId = is_array($sideItem) ? $sideItem['id'] : $sideItem;
            $extraPrice = is_array($sideItem) ? ($sideItem['extra_price'] ?? 0) : 0;
            
            $sideStmt->execute([
                'item_id' => $foodItemId,
                'side_id' => $sideId,
                'extra_price' => $extraPrice
            ]);
        }
    }
}

// Helper method to create food item packs with configuration
private function createFoodItemPacksWithConfig($foodItemId, $packsData)
{
    // First, create the food item packs configuration
    $configSql = "INSERT INTO food_item_packs_config (item_id, required, max_quantity) 
                  VALUES (:item_id, :required, :max_quantity)";
    $configStmt = $this->conn->prepare($configSql);
    $configStmt->execute([
        'item_id' => $foodItemId,
        'required' => $packsData['required'] ?? false,
        'max_quantity' => $packsData['max_quantity'] ?? 0
    ]);

    // Then, create the pack relationships
    if (!empty($packsData['items']) && is_array($packsData['items'])) {
        $packSql = "INSERT INTO food_item_packs (item_id, pack_id, extra_price) VALUES (:item_id, :pack_id, :extra_price)";
        $packStmt = $this->conn->prepare($packSql);

        foreach ($packsData['items'] as $packItem) {
            $packId = is_array($packItem) ? $packItem['id'] : $packItem;
            $extraPrice = is_array($packItem) ? ($packItem['extra_price'] ?? 0) : 0;
            
            $packStmt->execute([
                'item_id' => $foodItemId,
                'pack_id' => $packId,
                'extra_price' => $extraPrice
            ]);
        }
    }
}

// Helper method to create food item sections with configuration
private function createFoodItemSectionsWithConfig($foodItemId, $sectionsData)
{
    // First, create the food item sections configuration
    $configSql = "INSERT INTO food_item_sections_config (item_id, required, max_quantity) 
                  VALUES (:item_id, :required, :max_quantity)";
    $configStmt = $this->conn->prepare($configSql);
    $configStmt->execute([
        'item_id' => $foodItemId,
        'required' => $sectionsData['required'] ?? false,
        'max_quantity' => $sectionsData['max_quantity'] ?? 0
    ]);

    // Then, create the section relationships
    if (!empty($sectionsData['items']) && is_array($sectionsData['items'])) {
        $sectionSql = "INSERT INTO food_item_sections (item_id, section_id) VALUES (:item_id, :section_id)";
        $sectionStmt = $this->conn->prepare($sectionSql);

        foreach ($sectionsData['items'] as $sectionId) {
            $sectionStmt->execute([
                'item_id' => $foodItemId,
                'section_id' => $sectionId
            ]);
        }
    }
}

}


