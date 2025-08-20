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
        if (isset($data['sides']) && is_array($data['sides'])) {
            // Check if it's the old format (object with items array)
            if (isset($data['sides']['items']) && is_array($data['sides']['items'])) {
                // Old format
                if (!empty($data['sides']['items'])) {
                    $this->createFoodItemSidesWithConfig($foodItemId, $data['sides']);
                }
            } else {
                // New format - array of objects with id
                if (!empty($data['sides'])) {
                    $this->createFoodItemSidesFromArray($foodItemId, $data['sides']);
                }
            }
        }

        // Handle packs if provided
        if (isset($data['packs']) && is_array($data['packs'])) {
            // Check if it's the old format (object with items array)
            if (isset($data['packs']['items']) && is_array($data['packs']['items'])) {
                // Old format
                if (!empty($data['packs']['items'])) {
                    $this->createFoodItemPacksWithConfig($foodItemId, $data['packs']);
                }
            } else {
                // New format - array of objects with id
                if (!empty($data['packs'])) {
                    $this->createFoodItemPacksFromArray($foodItemId, $data['packs']);
                }
            }
        }

        // Handle sections if provided
        if (isset($data['sections']) && is_array($data['sections'])) {
            // Check if it's the old format (object with items array)
            if (isset($data['sections']['items']) && is_array($data['sections']['items'])) {
                // Old format
                if (!empty($data['sections']['items'])) {
                    $this->createFoodItemSectionsWithConfig($foodItemId, $data['sections']);
                }
            } else {
                // New format - array of objects with enhanced section support
                if (!empty($data['sections'])) {
                    $this->createFoodItemSectionsFromArray($foodItemId, $data['sections']);
                }
            }
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
        // Rollback on error if transaction is active
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
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

    // Get sections (entire sections linked to this food item)
    $sectionsSql = "SELECT fs.* 
                    FROM food_sections fs 
                    INNER JOIN food_item_sections fis ON fs.id = fis.section_id 
                    WHERE fis.item_id = :item_id";
    $sectionsStmt = $this->conn->prepare($sectionsSql);
    $sectionsStmt->execute(['item_id' => $foodItemId]);
    $sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get section items configuration
    $sectionItemsConfigSql = "SELECT required, max_quantity FROM food_item_section_items_config WHERE item_id = :item_id";
    $sectionItemsConfigStmt = $this->conn->prepare($sectionItemsConfigSql);
    $sectionItemsConfigStmt->execute(['item_id' => $foodItemId]);
    $sectionItemsConfig = $sectionItemsConfigStmt->fetch(PDO::FETCH_ASSOC);

    // Get specific section items linked to this food item
    $sectionItemsSql = "SELECT fsi.*, fisi.extra_price 
                        FROM food_section_items fsi 
                        INNER JOIN food_item_section_items fisi ON fsi.id = fisi.section_item_id 
                        WHERE fisi.item_id = :item_id";
    $sectionItemsStmt = $this->conn->prepare($sectionItemsSql);
    $sectionItemsStmt->execute(['item_id' => $foodItemId]);
    $sectionItems = $sectionItemsStmt->fetchAll(PDO::FETCH_ASSOC);

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

    $foodItem['section_items'] = [
        'required' => $sectionItemsConfig ? (bool)$sectionItemsConfig['required'] : false,
        'max_quantity' => $sectionItemsConfig ? (int)$sectionItemsConfig['max_quantity'] : 0,
        'items' => $sectionItems
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
        // Check if another item with the same name exists in the store (excluding current item)
        $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id AND name = :name AND id != :id AND deleted = 0");
        $nameCheck->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'id' => $data['id']
        ]);
        if ($nameCheck->fetchColumn() > 0) {
            throw new \Exception('Item with this name already exists in this store. Please choose a different name.');
        }

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
    // Get All Food Items by Store (with pagination)
    public function getAllByStoreId($store_id, $limit = null, $offset = null)
    {
        $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                       fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                       d.id as discount_id,
                       d.percentage as discount_percentage,
                       d.start_date as discount_start_date,
                       d.end_date as discount_end_date,
                       (fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)) as calculated_discount_price,
                       COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                FROM {$this->table} fi
                LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
                LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                    AND NOW() BETWEEN d.start_date AND d.end_date
                LEFT JOIN order_items oi ON fi.id = oi.item_id
                WHERE fi.store_id = :store_id AND fi.deleted = 0 
                GROUP BY fi.id
                ORDER BY fi.created_at DESC";
        
        // Add pagination if limit is provided
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        
        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric fields to appropriate types for each result
        foreach ($results as &$result) {
            $result['price'] = (float)$result['price'];
            $result['max_qty'] = (int)$result['max_qty'];
            $result['total_orders'] = (int)$result['total_orders'];
            $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
            $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
            $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
            
            // Fix date formatting - use DATE() function to avoid timezone issues
            $result['discount_start_date'] = $result['discount_start_date'] ? $result['discount_start_date'] : null;
            $result['discount_end_date'] = $result['discount_end_date'] ? $result['discount_end_date'] : null;
        }
        
        return $results;
    }

    // Get All Food Items by Store (paginated version)
    public function getAllByStoreIdPaginated($store_id, $limit, $offset)
    {
        return $this->getAllByStoreId($store_id, $limit, $offset);
    }

    // Count total food items by store
    public function countFoodItemsByStoreId($store_id)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id AND deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Get a single Food Item by id
public function getByItemId($id)
{
    $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                   fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                   d.id as discount_id,
                   d.percentage as discount_percentage,
                   d.start_date as discount_start_date,
                   d.end_date as discount_end_date,
                   (fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)) as calculated_discount_price,
                   COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
            FROM {$this->table} fi
            LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
            LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                AND NOW() BETWEEN d.start_date AND d.end_date
            LEFT JOIN order_items oi ON fi.id = oi.item_id
            WHERE fi.id = :id
            GROUP BY fi.id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Convert numeric fields to appropriate types
        $result['price'] = (float)$result['price'];
        $result['max_qty'] = (int)$result['max_qty'];
        $result['total_orders'] = (int)$result['total_orders'];
        $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
        $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
        $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
        
        // Fix date formatting - use DATE() function to avoid timezone issues
        $result['discount_start_date'] = $result['discount_start_date'] ? $result['discount_start_date'] : null;
        $result['discount_end_date'] = $result['discount_end_date'] ? $result['discount_end_date'] : null;
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
                         d.percentage as discount_percentage,
                         d.start_date as discount_start_date,
                         d.end_date as discount_end_date,
                         (fs.price - (fs.price * COALESCE(d.percentage, 0) / 100)) as calculated_discount_price,
                         COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                  FROM food_sides fs
                  LEFT JOIN discount_items di ON fs.id = di.item_id AND di.item_type = 'side'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                      AND NOW() BETWEEN d.start_date AND d.end_date
                  LEFT JOIN food_item_sides fis ON fs.id = fis.side_id
                  LEFT JOIN order_items oi ON fis.item_id = oi.item_id
                  WHERE fs.store_id = :store_id 
                  GROUP BY fs.id
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
            $result['total_orders'] = (int)$result['total_orders'];
            $result['price'] = (float)$result['price'];
            $result['discount_price'] = (float)$result['discount_price'];
            $result['percentage'] = (float)$result['percentage'];
            $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
            $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
            $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
            
            // Fix date formatting - use DATE() function to avoid timezone issues
            $result['discount_start_date'] = $result['discount_start_date'] ? $result['discount_start_date'] : null;
            $result['discount_end_date'] = $result['discount_end_date'] ? $result['discount_end_date'] : null;
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
            if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
            }
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
            if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
            }
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
            if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
            }
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
// Check if section name already exists for this store
private function sectionNameExists($storeId, $sectionName)
{
    $query = "SELECT COUNT(*) FROM food_sections WHERE store_id = :store_id AND section_name = :section_name";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId);
    $stmt->bindParam(':section_name', $sectionName);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// CREATE Food Section (ensure unique section name per store)
public function createFoodSection($data)
{
    try {
        // Check for duplicate section name
        if ($this->sectionNameExists($data['store_id'], $data['section_name'])) {
            throw new \Exception("A section with the name '{$data['section_name']}' already exists in your store.");
        }

        // Begin transaction
        $this->conn->beginTransaction();

        // Set defaults if not provided
        $maxQuantity = isset($data['max_qty']) ? $data['max_qty'] : null;
        $required = isset($data['is_required']) ? $data['is_required'] : 0;

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

        // Create section items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            $itemQuery = "INSERT INTO food_section_items (section_id, name, price) VALUES (:section_id, :name, :price)";
            $itemStmt = $this->conn->prepare($itemQuery);

            foreach ($data['items'] as $item) {
                $itemStmt->bindValue(':section_id', $sectionId);
                $itemStmt->bindValue(':name', $item['name']);
                $itemStmt->bindValue(':price', $item['price']);
                $itemStmt->execute();
            }
        }

        // Commit transaction
        $this->conn->commit();
        
        // Get the created items if any
        $items = [];
        if (!empty($data['items']) && is_array($data['items'])) {
            $itemQuery = "SELECT id, name, price, status 
                          FROM food_section_items 
                          WHERE section_id = :section_id AND status = 'active'";
            $itemStmt = $this->conn->prepare($itemQuery);
            $itemStmt->bindParam(':section_id', $sectionId);
            $itemStmt->execute();
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Return the created section data with items
        return [
            'id' => $sectionId,
            'store_id' => $data['store_id'],
            'section_name' => $data['section_name'],
            'max_quantity' => $maxQuantity,
            'required' => $required,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'items' => $items
        ];

    } catch (\PDOException $e) {
        // Rollback on error
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        throw new \Exception("Error creating food section: " . $e->getMessage());
    }
}


// READ All Sections by Store (with pagination)
public function getAllFoodSectionsByStoreId($storeId, $limit = null, $offset = null)
{
    // First, get all sections for this store with pagination
    $query = "SELECT * FROM food_sections WHERE store_id = :store_id ORDER BY created_at DESC";
    
    // Add pagination if limit is provided
    if ($limit !== null) {
        $query .= " LIMIT :limit";
        if ($offset !== null) {
            $query .= " OFFSET :offset";
        }
    }
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    
    if ($limit !== null) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        if ($offset !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
    }
    
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each section, get attached items
    foreach ($sections as &$section) {
        $itemQuery = "SELECT id, name, price, status 
                      FROM food_section_items 
                      WHERE section_id = :section_id AND status = 'active'";
        $itemStmt = $this->conn->prepare($itemQuery);
        $itemStmt->bindParam(':section_id', $section['id']);
        $itemStmt->execute();
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach items to this section
        $section['items'] = $items;
    }

    return $sections;
}

// READ All Sections by Store (paginated version)
public function getAllFoodSectionsByStoreIdPaginated($storeId, $limit, $offset)
{
    return $this->getAllFoodSectionsByStoreId($storeId, $limit, $offset);
}

// Count total food sections by store
public function countFoodSectionsByStoreId($storeId)
{
    $query = "SELECT COUNT(*) FROM food_sections WHERE store_id = :store_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
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
        // Get attached items
        $itemQuery = "SELECT id, name, price, status 
                      FROM food_section_items 
                      WHERE section_id = :id AND status = 'active'";
        $itemStmt = $this->conn->prepare($itemQuery);
        $itemStmt->bindParam(':id', $id);
        $itemStmt->execute();
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $section['items'] = $items;
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
        $maxQuantity = isset($data['max_qty']) ? $data['max_qty'] : null;
        $required = isset($data['is_required']) ? $data['is_required'] : 0;

        // Update the section details if provided
        $query = "UPDATE food_sections SET ";
        $params = [];
        
        // Only update section_name if provided
        if (isset($data['section_name']) && !empty($data['section_name'])) {
            $query .= "section_name = :section_name, ";
            $params[':section_name'] = $data['section_name'];
        }
        
        // Update max_quantity and required if provided
        if (isset($data['max_qty'])) {
            $query .= "max_quantity = :max_quantity, ";
            $params[':max_quantity'] = $data['max_qty'];
        }
        
        if (isset($data['is_required'])) {
            $query .= "required = :required, ";
            $params[':required'] = $data['is_required'];
        }
        
        // Remove trailing comma and add WHERE clause
        $query = rtrim($query, ', ');
        $query .= " WHERE id = :section_id";
        $params[':section_id'] = $data['section_id'];
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        // Handle items if provided - ADD new items to existing ones
        if (isset($data['items']) && is_array($data['items'])) {
            // Insert the new items (don't delete existing ones)
            if (!empty($data['items'])) {
                $insertQuery = "INSERT INTO food_section_items (section_id, name, price) VALUES (:section_id, :name, :price)";
                $insertStmt = $this->conn->prepare($insertQuery);

                foreach ($data['items'] as $item) {
                    $insertStmt->bindValue(':section_id', $data['section_id']);
                    $insertStmt->bindValue(':name', $item['name']);
                    $insertStmt->bindValue(':price', $item['price']);
                    $insertStmt->execute();
                }
            }
        }

        // Commit transaction
        $this->conn->commit();
        
        // Get the updated section data with items
        $sectionId = $data['section_id'];
        $sectionQuery = "SELECT * FROM food_sections WHERE id = :section_id";
        $sectionStmt = $this->conn->prepare($sectionQuery);
        $sectionStmt->execute(['section_id' => $sectionId]);
        $sectionData = $sectionStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get the items for this section
        $itemsQuery = "SELECT id, name, price, status 
                      FROM food_section_items 
                      WHERE section_id = :section_id AND status = 'active'";
        $itemsStmt = $this->conn->prepare($itemsQuery);
        $itemsStmt->execute(['section_id' => $sectionId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return the updated section data with items
        return [
            'id' => $sectionData['id'],
            'store_id' => $sectionData['store_id'],
            'section_name' => $sectionData['section_name'],
            'max_quantity' => $sectionData['max_quantity'],
            'required' => $sectionData['required'],
            'created_at' => $sectionData['created_at'],
            'updated_at' => $sectionData['updated_at'],
            'items' => $items
        ];

    } catch (PDOException $e) {
        // Rollback on error
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
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
    $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                   fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                   d.id as discount_id,
                   d.percentage as discount_percentage,
                   d.start_date as discount_start_date,
                   d.end_date as discount_end_date,
                   (fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)) as calculated_discount_price,
                   COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
            FROM food_items fi
            LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
            LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                AND NOW() BETWEEN d.start_date AND d.end_date
            LEFT JOIN order_items oi ON fi.id = oi.item_id
            WHERE fi.store_id = :store_id AND fi.category_id = :category_id AND fi.deleted = 0
            GROUP BY fi.id";
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
        $result['total_orders'] = (int)$result['total_orders'];
        $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
        $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
        $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
        
        // Fix date formatting - use DATE() function to avoid timezone issues
        $result['discount_start_date'] = $result['discount_start_date'] ? $result['discount_start_date'] : null;
        $result['discount_end_date'] = $result['discount_end_date'] ? $result['discount_end_date'] : null;
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
    $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                   fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                   d.id as discount_id,
                   d.percentage as discount_percentage,
                   d.start_date as discount_start_date,
                   d.end_date as discount_end_date,
                   (fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)) as calculated_discount_price,
                   COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
            FROM food_items fi
            LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
            LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                AND NOW() BETWEEN d.start_date AND d.end_date
            LEFT JOIN order_items oi ON fi.id = oi.item_id
            WHERE fi.store_id = :store_id AND fi.category_id = :category_id AND fi.deleted = 0
            GROUP BY fi.id
            ORDER BY fi.created_at DESC 
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
        $result['total_orders'] = (int)$result['total_orders'];
        $result['discount_id'] = $result['discount_id'] ? (int)$result['discount_id'] : null;
        $result['discount_percentage'] = $result['discount_percentage'] ? (float)$result['discount_percentage'] : null;
        $result['calculated_discount_price'] = $result['calculated_discount_price'] ? (float)$result['calculated_discount_price'] : null;
        
        // Fix date formatting - use DATE() function to avoid timezone issues
        $result['discount_start_date'] = $result['discount_start_date'] ? $result['discount_start_date'] : null;
        $result['discount_end_date'] = $result['discount_end_date'] ? $result['discount_end_date'] : null;
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

    // New method to handle sides from array format
    private function createFoodItemSidesFromArray($foodItemId, $sidesArray)
    {
        // Create default config
        $configSql = "INSERT INTO food_item_sides_config (item_id, required, max_quantity, created_at) VALUES (:item_id, false, 0, NOW())";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute(['item_id' => $foodItemId]);

        // Add each side
        foreach ($sidesArray as $side) {
            $sideId = null;
            $extraPrice = 0;
            
            if (is_numeric($side)) {
                // Simple ID format
                $sideId = $side;
            } elseif (is_array($side) && isset($side['id']) && is_numeric($side['id'])) {
                // Object format with id and optional extra_price
                $sideId = $side['id'];
                $extraPrice = $side['extra_price'] ?? 0;
            }
            
            if ($sideId) {
                $sql = "INSERT INTO food_item_sides (item_id, side_id, extra_price, created_at) VALUES (:item_id, :side_id, :extra_price, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'item_id' => $foodItemId,
                    'side_id' => $sideId,
                    'extra_price' => $extraPrice
                ]);
            }
        }
    }

    // New method to handle packs from array format
    private function createFoodItemPacksFromArray($foodItemId, $packsArray)
    {
        // Create default config
        $configSql = "INSERT INTO food_item_packs_config (item_id, required, max_quantity, created_at) VALUES (:item_id, false, 0, NOW())";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute(['item_id' => $foodItemId]);

        // Add each pack
        foreach ($packsArray as $pack) {
            $packId = null;
            $extraPrice = 0;
            
            if (is_numeric($pack)) {
                // Simple ID format
                $packId = $pack;
            } elseif (is_array($pack) && isset($pack['id']) && is_numeric($pack['id'])) {
                // Object format with id and optional extra_price
                $packId = $pack['id'];
                $extraPrice = $pack['extra_price'] ?? 0;
            }
            
            if ($packId) {
                $sql = "INSERT INTO food_item_packs (item_id, pack_id, extra_price, created_at) VALUES (:item_id, :pack_id, :extra_price, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'item_id' => $foodItemId,
                    'pack_id' => $packId,
                    'extra_price' => $extraPrice
                ]);
            }
        }
    }

    // Enhanced method to handle sections from array format with selective item support
    private function createFoodItemSectionsFromArray($foodItemId, $sectionsArray)
    {
        // Create default config
        $configSql = "INSERT INTO food_item_sections_config (item_id, required, max_quantity, created_at) VALUES (:item_id, false, 0, NOW())";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute(['item_id' => $foodItemId]);

        // Add each section
        foreach ($sectionsArray as $section) {
            if (isset($section['id']) && is_numeric($section['id'])) {
                // Check if this section has selected items
                if (isset($section['selected_items']) && is_array($section['selected_items']) && !empty($section['selected_items'])) {
                    // Enhanced format: Link specific section items
                    $this->createFoodItemSectionItemsFromArray($foodItemId, $section['selected_items']);
                } else {
                    // Simple format: Link entire section (backward compatible)
                $sql = "INSERT INTO food_item_sections (item_id, section_id, created_at) VALUES (:item_id, :section_id, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'item_id' => $foodItemId,
                    'section_id' => $section['id']
                    ]);
                }
            }
        }
    }

    // New method to handle section items from array format
    private function createFoodItemSectionItemsFromArray($foodItemId, $sectionItemsArray)
    {
        // Create default config for section items
        $configSql = "INSERT INTO food_item_section_items_config (item_id, required, max_quantity, created_at) VALUES (:item_id, false, 0, NOW())";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute(['item_id' => $foodItemId]);

        // Add each section item
        foreach ($sectionItemsArray as $sectionItem) {
            if (is_numeric($sectionItem)) {
                // Simple format: just the section item ID
                $sql = "INSERT INTO food_item_section_items (item_id, section_item_id, created_at) VALUES (:item_id, :section_item_id, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'item_id' => $foodItemId,
                    'section_item_id' => $sectionItem
                ]);
            } elseif (is_array($sectionItem) && isset($sectionItem['id']) && is_numeric($sectionItem['id'])) {
                // Enhanced format: section item object with extra_price
                $extraPrice = isset($sectionItem['extra_price']) ? $sectionItem['extra_price'] : 0;
                $sql = "INSERT INTO food_item_section_items (item_id, section_item_id, extra_price, created_at) VALUES (:item_id, :section_item_id, :extra_price, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'item_id' => $foodItemId,
                    'section_item_id' => $sectionItem['id'],
                    'extra_price' => $extraPrice
                ]);
            }
        }
    }

    // Set food item status (activate/deactivate)
    public function setFoodItemStatus($id, $status)
    {
        try {
            $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("setFoodItemStatus error: " . $e->getMessage());
            return false;
        }
    }

    // Set food section status (activate/deactivate)
    public function setFoodSectionStatus($id, $status)
    {
        try {
            $sql = "UPDATE food_sections SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("setFoodSectionStatus error: " . $e->getMessage());
            return false;
        }
    }

    // Bulk update food sections status
    public function updateFoodSectionsStatusBulk($ids, $status)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE food_sections SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $params = array_merge([$status], $ids);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("updateFoodSectionsStatusBulk error: " . $e->getMessage());
            return false;
        }
    }

    // Bulk delete food sections
    public function deleteFoodSectionsBulk($ids)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM food_sections WHERE id IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($ids);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("deleteFoodSectionsBulk error: " . $e->getMessage());
            return false;
        }
    }
}