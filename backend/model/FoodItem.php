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

    /**
     * Ensure food_sections table exists
     */
    private function ensureFoodSectionsTableExists()
    {
        try {
            // Check if table exists
            $checkQuery = "SHOW TABLES LIKE 'food_sections'";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute();
            $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tableExists) {
                error_log("food_sections table does not exist, creating it...");
                
                // Create the table
                $createTableQuery = "CREATE TABLE IF NOT EXISTS food_sections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    store_id INT NOT NULL,
                    section_name VARCHAR(255) NOT NULL,
                    max_quantity INT DEFAULT 0,
                    required BOOLEAN DEFAULT FALSE,
                    price DECIMAL(10,2) DEFAULT 0.00,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_store_id (store_id),
                    INDEX idx_status (status)
                )";
                
                $stmt = $this->conn->prepare($createTableQuery);
                $stmt->execute();
                error_log("food_sections table created successfully");
            }
        } catch (Exception $e) {
            error_log("Error ensuring food_sections table exists: " . $e->getMessage());
        }
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
            (store_id, category_id, section_id, user_id, name, price, photo, short_description, status, created_at, updated_at)
            VALUES 
            (:store_id, :category_id, :section_id, :user_id, :name, :price, :photo, :short_description, :status, NOW(), NOW())";

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
                (store_id, category_id, section_id, user_id, name, price, photo, short_description, status, created_at, updated_at)
                VALUES 
                (:store_id, :category_id, :section_id, :user_id, :name, :price, :photo, :short_description, :status, NOW(), NOW())";

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

        // Handle sections if provided (new preferred format)
        if (isset($data['sections']) && is_array($data['sections'])) {
            // Check if it's the new preferred format (array of section objects with section_id and item_ids)
            if (isset($data['sections'][0]) && is_array($data['sections'][0]) && isset($data['sections'][0]['section_id'])) {
                // New preferred format - array of section objects
                if (!empty($data['sections'])) {
                    $this->createFoodItemSectionsWithItems($foodItemId, $data['sections']);
                }
            } elseif (isset($data['sections']['items']) && is_array($data['sections']['items'])) {
                // Legacy format - single object with items array
                if (!empty($data['sections']['items'])) {
                    $this->createFoodItemSectionsWithConfig($foodItemId, $data['sections']);
                }
            } else {
                // Legacy format - array of objects with id
                if (!empty($data['sections'])) {
                    $this->createFoodItemSectionsFromArray($foodItemId, $data['sections']);
                }
            }
        }

        // Note: section_items handling removed - now handled within sections structure

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
        // Re-throw the exception so the controller can handle it properly
        throw $e;
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

    // Build the complete response - return only IDs for consistency
    $sidesIds = array_map('intval', array_column($sides, 'id'));
    $packsIds = array_map('intval', array_column($packs, 'id'));
    $sectionsIds = array_map('intval', array_column($sections, 'id'));
    $sectionItemsIds = array_map('intval', array_column($sectionItems, 'id'));

    $foodItem['sides'] = [
        'required' => $sidesConfig ? (bool)$sidesConfig['required'] : false,
        'max_quantity' => $sidesConfig ? (int)$sidesConfig['max_quantity'] : 0,
        'items' => $sidesIds
    ];

    $foodItem['packs'] = [
        'required' => $packsConfig ? (bool)$packsConfig['required'] : false,
        'max_quantity' => $packsConfig ? (int)$packsConfig['max_quantity'] : 0,
        'items' => $packsIds
    ];

    // Build sections with their associated items in the new preferred format
    $sectionsArray = [];
    foreach ($sections as $section) {
        $sectionId = $section['id'];
        
        // Get section items for this specific section
        $sectionItemsSql = "SELECT fisi.section_item_id 
                           FROM food_item_section_items fisi 
                           JOIN food_section_items fsi ON fisi.section_item_id = fsi.id 
                           WHERE fisi.item_id = :item_id AND fsi.section_id = :section_id";
        $sectionItemsStmt = $this->conn->prepare($sectionItemsSql);
        $sectionItemsStmt->execute([
            'item_id' => $foodItemId,
            'section_id' => $sectionId
        ]);
        $sectionItemIds = array_map('intval', array_column($sectionItemsStmt->fetchAll(PDO::FETCH_ASSOC), 'section_item_id'));
        
        $sectionsArray[] = [
            'section_id' => (int)$sectionId,
        'required' => $sectionsConfig ? (bool)$sectionsConfig['required'] : false,
        'max_quantity' => $sectionsConfig ? (int)$sectionsConfig['max_quantity'] : 0,
            'item_ids' => $sectionItemIds
        ];
    }
    
    $foodItem['sections'] = $sectionsArray;
    
    // Remove section_items from response as it's now part of sections
    unset($foodItem['section_items']);

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
        try {
            // Begin transaction
            $this->conn->beginTransaction();

        // Check if another item with the same name exists in the store (excluding current item)
        error_log("Checking for duplicate name: " . $data['name'] . " in store: " . $data['store_id'] . " (excluding item: " . $data['id'] . ")");
        $nameCheck = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE store_id = :store_id AND name = :name AND id != :id AND deleted = 0");
        $nameCheck->execute([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'id' => $data['id']
        ]);
        $duplicateCount = $nameCheck->fetchColumn();
        error_log("Duplicate name check result: " . $duplicateCount . " items found");
        
        if ($duplicateCount > 0) {
            error_log("Duplicate name detected - throwing exception");
            throw new \Exception('Item with this name already exists in this store. Please choose a different name.');
        }

            // Update basic food item fields
        $query = "UPDATE food_items SET 
                    name = :name,
                    short_description = :short_description,
                    price = :price,
                        category_id = :category_id,
                        section_id = :section_id,
                    status = :status,
                    updated_at = NOW()";

        // Always include photo field in update
        $query .= ", photo = :photo";

        $query .= " WHERE id = :id";
            $categoryId = $data['category_id'] ?? null;
        $sectionId = $data['section_id'] ?? null;

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':short_description', $data['short_description']);
        $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category_id', $categoryId);
        $stmt->bindParam(':section_id', $sectionId);
        $stmt->bindParam(':status', $data['status']);

        // Always bind photo parameter
        $stmt->bindParam(':photo', $data['photo']);

            $stmt->execute();

            // Handle sides if provided
            if (isset($data['sides']) && is_array($data['sides'])) {
                error_log("Updating sides for food item: " . $data['id']);
                $this->updateFoodItemSides($data['id'], $data['sides']);
            }

            // Handle packs if provided
            if (isset($data['packs']) && is_array($data['packs'])) {
                error_log("Updating packs for food item: " . $data['id']);
                $this->updateFoodItemPacks($data['id'], $data['packs']);
            }

            // Handle sections if provided
            if (isset($data['sections']) && is_array($data['sections'])) {
                error_log("Updating sections for food item: " . $data['id']);
                $this->updateFoodItemSections($data['id'], $data['sections']);
            }

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            
            // Enhanced error logging for debugging
            error_log("=== FOOD ITEM MODEL UPDATE ERROR ===");
            error_log("Error Message: " . $e->getMessage());
            error_log("Error File: " . $e->getFile());
            error_log("Error Line: " . $e->getLine());
            error_log("Error Trace: " . $e->getTraceAsString());
            error_log("Update Data: " . json_encode($data));
            error_log("Database Connection Status: " . ($this->conn ? 'Connected' : 'Not Connected'));
            
            // Check for specific database errors
            if ($this->conn) {
                $errorInfo = $this->conn->errorInfo();
                error_log("PDO Error Info: " . json_encode($errorInfo));
            }
            
            error_log("=== END MODEL ERROR LOG ===");
            throw $e;
        }
    }

    // Helper method to update food item sides
    private function updateFoodItemSides($foodItemId, $sidesData)
    {
        try {
            error_log("=== UPDATING FOOD ITEM SIDES ===");
            error_log("Food Item ID: " . $foodItemId);
            error_log("Sides Data: " . json_encode($sidesData));
            
            // First, delete existing sides relationships
            $deleteConfigSql = "DELETE FROM food_item_sides_config WHERE item_id = :item_id";
            $deleteConfigStmt = $this->conn->prepare($deleteConfigSql);
            $deleteConfigStmt->execute(['item_id' => $foodItemId]);

            $deleteSidesSql = "DELETE FROM food_item_sides WHERE item_id = :item_id";
            $deleteSidesStmt = $this->conn->prepare($deleteSidesSql);
            $deleteSidesStmt->execute(['item_id' => $foodItemId]);

            // Check if it's the structured format (object with required, max_quantity, items)
            if (isset($sidesData['required']) || isset($sidesData['max_quantity']) || isset($sidesData['items'])) {
                // Structured format - create new config and relationships
                $this->createFoodItemSidesWithConfig($foodItemId, $sidesData);
            } else {
                // Simple format - array of side IDs
                $this->createFoodItemSidesFromArray($foodItemId, $sidesData);
            }
            
            error_log("Sides update completed successfully");
        } catch (\Exception $e) {
            error_log("=== SIDES UPDATE ERROR ===");
            error_log("Error: " . $e->getMessage());
            error_log("Food Item ID: " . $foodItemId);
            error_log("Sides Data: " . json_encode($sidesData));
            throw $e;
        }
    }

    // Helper method to update food item packs
    private function updateFoodItemPacks($foodItemId, $packsData)
    {
        try {
            error_log("=== UPDATING FOOD ITEM PACKS ===");
            error_log("Food Item ID: " . $foodItemId);
            error_log("Packs Data: " . json_encode($packsData));
            
            // First, delete existing packs relationships
            $deleteConfigSql = "DELETE FROM food_item_packs_config WHERE item_id = :item_id";
            $deleteConfigStmt = $this->conn->prepare($deleteConfigSql);
            $deleteConfigStmt->execute(['item_id' => $foodItemId]);

            $deletePacksSql = "DELETE FROM food_item_packs WHERE item_id = :item_id";
            $deletePacksStmt = $this->conn->prepare($deletePacksSql);
            $deletePacksStmt->execute(['item_id' => $foodItemId]);

            // Check if it's the structured format (object with required, max_quantity, items)
            if (isset($packsData['required']) || isset($packsData['max_quantity']) || isset($packsData['items'])) {
                // Structured format - create new config and relationships
                $this->createFoodItemPacksWithConfig($foodItemId, $packsData);
            } else {
                // Simple format - array of pack IDs
                $this->createFoodItemPacksFromArray($foodItemId, $packsData);
            }
            
            error_log("Packs update completed successfully");
        } catch (\Exception $e) {
            error_log("=== PACKS UPDATE ERROR ===");
            error_log("Error: " . $e->getMessage());
            error_log("Food Item ID: " . $foodItemId);
            error_log("Packs Data: " . json_encode($packsData));
            throw $e;
        }
    }

    // Helper method to update food item sections
    private function updateFoodItemSections($foodItemId, $sectionsData)
    {
        try {
            error_log("=== UPDATING FOOD ITEM SECTIONS ===");
            error_log("Food Item ID: " . $foodItemId);
            error_log("Sections Data: " . json_encode($sectionsData));
            
            // First, delete existing sections relationships
            $deleteConfigSql = "DELETE FROM food_item_sections_config WHERE item_id = :item_id";
            $deleteConfigStmt = $this->conn->prepare($deleteConfigSql);
            $deleteConfigStmt->execute(['item_id' => $foodItemId]);

            $deleteSectionsSql = "DELETE FROM food_item_sections WHERE item_id = :item_id";
            $deleteSectionsStmt = $this->conn->prepare($deleteSectionsSql);
            $deleteSectionsStmt->execute(['item_id' => $foodItemId]);

            // Check if it's the new preferred format (array of section objects)
            if (isset($sectionsData[0]) && is_array($sectionsData[0]) && isset($sectionsData[0]['section_id'])) {
                // New preferred format - array of section objects
                $this->createFoodItemSectionsWithItems($foodItemId, $sectionsData);
            } elseif (isset($sectionsData['required']) || isset($sectionsData['max_quantity']) || isset($sectionsData['items'])) {
                // Legacy format - single object with items array
                $this->createFoodItemSectionsWithConfig($foodItemId, $sectionsData);
            } else {
                // Legacy format - array of objects with id
                $this->createFoodItemSectionsFromArray($foodItemId, $sectionsData);
            }
            
            error_log("Sections update completed successfully");
        } catch (\Exception $e) {
            error_log("=== SECTIONS UPDATE ERROR ===");
            error_log("Error: " . $e->getMessage());
            error_log("Food Item ID: " . $foodItemId);
            error_log("Sections Data: " . json_encode($sectionsData));
            throw $e;
        }
    }


    // Soft Delete Food Item
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    public function itemExists($id) {
    error_log("=== FOOD ITEM MODEL itemExists DEBUG ===");
    error_log("Checking if food item exists with ID: " . $id);
    error_log("ID type: " . gettype($id));
    
    $query = "SELECT COUNT(*) FROM food_items WHERE id = :id AND deleted = 0";
    error_log("Executing query: " . $query . " with ID: " . $id);
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $id]);
    $count = $stmt->fetchColumn();
    
    error_log("Query result count: " . $count);
    $exists = $count > 0;
    error_log("Item exists result: " . ($exists ? 'true' : 'false'));
    
    // Also check what items exist with this ID regardless of deleted status
    $debugQuery = "SELECT id, name, deleted, store_id FROM food_items WHERE id = :id";
    $debugStmt = $this->conn->prepare($debugQuery);
    $debugStmt->execute(['id' => $id]);
    $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
    error_log("Debug - All items with this ID: " . json_encode($debugResult));
    
    return $exists;
}

    public function isFoodOwnedByUser($id, $userId)
{
    $sql = "SELECT id FROM {$this->table} WHERE id = :id AND user_id = :userId AND deleted = '0'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':id' => $id, ':userId' => $userId]);
    return $stmt->fetch() !== false;
}
    // Get All Food Items by Store (with pagination)
    public function getAllByStoreId($store_id, $limit = null, $offset = null, $active_only = false)
    {
        // First, get the basic food items
        $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                       fi.short_description, fi.status, fi.deleted, fi.created_at, fi.updated_at
                FROM {$this->table} fi
                WHERE fi.store_id = :store_id AND fi.deleted = 0
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
        
        error_log("=== FOOD ITEM SQL QUERY DEBUG ===");
        error_log("SQL Query: " . $sql);
        error_log("Store ID: " . $store_id);
        error_log("Limit: " . ($limit ?? 'null'));
        error_log("Offset: " . ($offset ?? 'null'));
        error_log("Active Only: " . ($active_only ? 'true' : 'false'));
        error_log("Current Date (CURDATE()): " . date('Y-m-d'));
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("=== FOOD ITEM QUERY RESULTS ===");
        error_log("Number of results: " . count($results));
        
        // Now enhance each result with discount, order and options information
        foreach ($results as &$result) {
            $result['price'] = (float)$result['price'];
            
            // Get discount information for this food item
            $discountSql = "SELECT d.id, d.percentage, d.start_date, d.end_date, d.status
                           FROM discounts d
                           INNER JOIN discount_items di ON d.id = di.discount_id
                           WHERE di.item_id = :item_id 
                             AND di.item_type = 'food_item'
                             AND d.store_id = :store_id
                             AND d.status = 'active'";
            
            if ($active_only) {
                $discountSql .= " AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                                 AND (d.end_date IS NULL OR d.end_date >= CURDATE())";
            }
            
            $discountStmt = $this->conn->prepare($discountSql);
            $discountStmt->execute([
                'item_id' => $result['id'],
                'store_id' => $store_id
            ]);
            $discount = $discountStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($discount) {
                $result['discount_id'] = (int)$discount['id'];
                $result['percentage'] = (float)$discount['percentage'];
                $result['discount_start_date'] = $discount['start_date'];
                $result['discount_end_date'] = $discount['end_date'];
                $result['discount_price'] = round($result['price'] - ($result['price'] * $discount['percentage'] / 100), 2);
            }
            // Don't add discount fields if no discount exists
            
            // Get order count for this food item
            $orderSql = "SELECT COUNT(DISTINCT order_id) as total_orders 
                        FROM order_items 
                        WHERE item_id = :item_id";
            $orderStmt = $this->conn->prepare($orderSql);
            $orderStmt->execute(['item_id' => $result['id']]);
            $orderCount = $orderStmt->fetch(PDO::FETCH_ASSOC);
            $result['total_orders'] = (int)($orderCount['total_orders'] ?? 0);
            // Attach sides config and details
            $sidesConfigStmt = $this->conn->prepare("SELECT required, max_quantity FROM food_item_sides_config WHERE item_id = :item_id LIMIT 1");
            $sidesConfigStmt->execute(['item_id' => $result['id']]);
            $sidesConfig = $sidesConfigStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $sidesDetailsStmt = $this->conn->prepare("
                SELECT fs.id, fs.name, fs.price, fis.extra_price 
                FROM food_sides fs 
                INNER JOIN food_item_sides fis ON fs.id = fis.side_id 
                WHERE fis.item_id = :item_id
            ");
            $sidesDetailsStmt->execute(['item_id' => $result['id']]);
            $sidesDetails = $sidesDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

            $result['sides'] = [
                'required' => $sidesConfig ? (bool)$sidesConfig['required'] : false,
                'max_quantity' => $sidesConfig ? (int)$sidesConfig['max_quantity'] : 0,
                'items' => array_map(function($side) {
                    return [
                        'id' => (int)$side['id'],
                        'name' => $side['name'],
                        'price' => (float)$side['price'],
                        'extra_price' => (float)$side['extra_price']
                    ];
                }, $sidesDetails),
            ];

            // Attach packs config and details
            $packsConfigStmt = $this->conn->prepare("SELECT required, max_quantity FROM food_item_packs_config WHERE item_id = :item_id LIMIT 1");
            $packsConfigStmt->execute(['item_id' => $result['id']]);
            $packsConfig = $packsConfigStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $packsDetailsStmt = $this->conn->prepare("
                SELECT p.id, p.name, p.price, fip.extra_price 
                FROM packages p 
                INNER JOIN food_item_packs fip ON p.id = fip.pack_id 
                WHERE fip.item_id = :item_id
            ");
            $packsDetailsStmt->execute(['item_id' => $result['id']]);
            $packsDetails = $packsDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

            $result['packs'] = [
                'required' => $packsConfig ? (bool)$packsConfig['required'] : false,
                'max_quantity' => $packsConfig ? (int)$packsConfig['max_quantity'] : 0,
                'items' => array_map(function($pack) {
                    return [
                        'id' => (int)$pack['id'],
                        'name' => $pack['name'],
                        'price' => (float)$pack['price'],
                        'extra_price' => (float)$pack['extra_price']
                    ];
                }, $packsDetails),
            ];

            // Get sections with their config data and details using proper JOIN
            $sectionsConfigStmt = $this->conn->prepare("
                SELECT 
                    fs.section_id,
                    fsc.required,
                    fsc.max_quantity,
                    fsections.section_name
                FROM food_item_sections fs
                LEFT JOIN food_item_sections_config fsc ON fs.item_id = fsc.item_id
                LEFT JOIN food_sections fsections ON fs.section_id = fsections.id
                WHERE fs.item_id = :item_id
            ");
            $sectionsConfigStmt->execute(['item_id' => $result['id']]);
            $sectionsConfigRows = $sectionsConfigStmt->fetchAll(PDO::FETCH_ASSOC);

            // Group sections by section_id to avoid duplicates
            $sectionsGrouped = [];
            foreach ($sectionsConfigRows as $row) {
                $sectionId = $row['section_id'];
                if (!isset($sectionsGrouped[$sectionId])) {
                    $sectionsGrouped[$sectionId] = [
                        'section_id' => $sectionId,
                        'section_name' => $row['section_name'] ?? null,
                        'required' => (bool)($row['required'] ?? false),
                        'max_quantity' => (int)($row['max_quantity'] ?? 0)
                    ];
                } else {
                    // If there are multiple configs for the same section, use the most restrictive
                    if ($row['required'] && !$sectionsGrouped[$sectionId]['required']) {
                        $sectionsGrouped[$sectionId]['required'] = true;
                    }
                    if ($row['max_quantity'] > $sectionsGrouped[$sectionId]['max_quantity']) {
                        $sectionsGrouped[$sectionId]['max_quantity'] = (int)$row['max_quantity'];
                    }
                }
            }

            // Build sections with their associated items in the new preferred format
            $sectionsArray = [];
            foreach ($sectionsGrouped as $sectionData) {
                $sectionId = $sectionData['section_id'];
                
                // Get section items with details for this specific section
                $sectionItemsSql = "SELECT fsi.id, fsi.name, fsi.price, fisi.extra_price
                                   FROM food_item_section_items fisi 
                                   JOIN food_section_items fsi ON fisi.section_item_id = fsi.id 
                                   WHERE fisi.item_id = :item_id AND fsi.section_id = :section_id";
                $sectionItemsStmt = $this->conn->prepare($sectionItemsSql);
                $sectionItemsStmt->execute([
                    'item_id' => $result['id'],
                    'section_id' => $sectionId
                ]);
                $sectionItemsDetails = $sectionItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $sectionsArray[] = [
                    'section_id' => (int)$sectionId,
                    'section_name' => $sectionData['section_name'],
                    'required' => $sectionData['required'],
                    'max_quantity' => $sectionData['max_quantity'],
                    'items' => array_map(function($item) {
                        return [
                            'id' => (int)$item['id'],
                            'name' => $item['name'],
                            'price' => (float)$item['price'],
                            'extra_price' => (float)$item['extra_price']
                        ];
                    }, $sectionItemsDetails)
                ];
            }
            $result['sections'] = $sectionsArray;

            // Note: section_items handling removed - now part of sections structure
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
public function getByItemId($id, $store_id = null)
{
    $sql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                   fi.short_description, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                   d.id as discount_id,
                   d.percentage as discount_percentage,
                   d.start_date as discount_start_date,
                   d.end_date as discount_end_date,
                   ROUND((fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)), 2) as calculated_discount_price,
                   COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
            FROM {$this->table} fi
            LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
            LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active' 
                AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                AND (d.end_date IS NULL OR d.end_date >= CURDATE())
            LEFT JOIN order_items oi ON fi.id = oi.item_id
            WHERE fi.id = :id";
    
    // Add store_id filter if provided
    if ($store_id !== null) {
        $sql .= " AND fi.store_id = :store_id AND (d.store_id IS NULL OR d.store_id = :store_id)";
    }
    
    $sql .= " GROUP BY fi.id";
    
    $stmt = $this->conn->prepare($sql);
    $params = ['id' => $id];
    if ($store_id !== null) {
        $params['store_id'] = $store_id;
    }
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Convert numeric fields to appropriate types
        $result['price'] = (float)$result['price'];
        $result['total_orders'] = (int)$result['total_orders'];
        
        // Only include discount fields if there's an active discount with valid discount_id and percentage > 0
        if ($result['discount_id'] && $result['discount_percentage'] && $result['discount_percentage'] > 0) {
            $result['discount_id'] = (int)$result['discount_id'];
            $result['percentage'] = (float)$result['discount_percentage'];
            $result['discount_price'] = (float)$result['calculated_discount_price'];
            $result['discount_start_date'] = $result['discount_start_date'];
            $result['discount_end_date'] = $result['discount_end_date'];
        } else {
            // Remove discount fields if no valid discount
            unset($result['discount_id']);
            unset($result['percentage']);
            unset($result['discount_price']);
            unset($result['discount_start_date']);
            unset($result['discount_end_date']);
        }
        // Always remove the internal discount fields
        unset($result['discount_percentage']);
        unset($result['calculated_discount_price']);

        // Attach sides config and details
        $sidesConfigStmt = $this->conn->prepare("SELECT required, max_quantity FROM food_item_sides_config WHERE item_id = :item_id LIMIT 1");
        $sidesConfigStmt->execute(['item_id' => $result['id']]);
        $sidesConfig = $sidesConfigStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $sidesDetailsStmt = $this->conn->prepare("
            SELECT fs.id, fs.name, fs.price, fis.extra_price 
            FROM food_sides fs 
            INNER JOIN food_item_sides fis ON fs.id = fis.side_id 
            WHERE fis.item_id = :item_id
        ");
        $sidesDetailsStmt->execute(['item_id' => $result['id']]);
        $sidesDetails = $sidesDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

        $result['sides'] = [
            'required' => $sidesConfig ? (bool)$sidesConfig['required'] : false,
            'max_quantity' => $sidesConfig ? (int)$sidesConfig['max_quantity'] : 0,
            'items' => array_map(function($side) {
                return [
                    'id' => (int)$side['id'],
                    'name' => $side['name'],
                    'price' => (float)$side['price'],
                    'extra_price' => (float)$side['extra_price']
                ];
            }, $sidesDetails),
        ];

        // Attach packs config and details
        $packsConfigStmt = $this->conn->prepare("SELECT required, max_quantity FROM food_item_packs_config WHERE item_id = :item_id LIMIT 1");
        $packsConfigStmt->execute(['item_id' => $result['id']]);
        $packsConfig = $packsConfigStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $packsDetailsStmt = $this->conn->prepare("
            SELECT p.id, p.name, p.price, fip.extra_price 
            FROM packages p 
            INNER JOIN food_item_packs fip ON p.id = fip.pack_id 
            WHERE fip.item_id = :item_id
        ");
        $packsDetailsStmt->execute(['item_id' => $result['id']]);
        $packsDetails = $packsDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

        $result['packs'] = [
            'required' => $packsConfig ? (bool)$packsConfig['required'] : false,
            'max_quantity' => $packsConfig ? (int)$packsConfig['max_quantity'] : 0,
            'items' => array_map(function($pack) {
                return [
                    'id' => (int)$pack['id'],
                    'name' => $pack['name'],
                    'price' => (float)$pack['price'],
                    'extra_price' => (float)$pack['extra_price']
                ];
            }, $packsDetails),
        ];

        // Get sections with their config data and details using proper JOIN
        $sectionsConfigStmt = $this->conn->prepare("
            SELECT 
                fs.section_id,
                fsc.required,
                fsc.max_quantity,
                fsections.section_name
            FROM food_item_sections fs
            LEFT JOIN food_item_sections_config fsc ON fs.item_id = fsc.item_id
            LEFT JOIN food_sections fsections ON fs.section_id = fsections.id
            WHERE fs.item_id = :item_id
        ");
        $sectionsConfigStmt->execute(['item_id' => $result['id']]);
        $sectionsConfigRows = $sectionsConfigStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group sections by section_id to avoid duplicates
        $sectionsGrouped = [];
        foreach ($sectionsConfigRows as $row) {
            $sectionId = $row['section_id'];
            if (!isset($sectionsGrouped[$sectionId])) {
                $sectionsGrouped[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $row['section_name'] ?? null,
                    'required' => (bool)($row['required'] ?? false),
                    'max_quantity' => (int)($row['max_quantity'] ?? 0)
                ];
            } else {
                // If there are multiple configs for the same section, use the most restrictive
                if ($row['required'] && !$sectionsGrouped[$sectionId]['required']) {
                    $sectionsGrouped[$sectionId]['required'] = true;
                }
                if ($row['max_quantity'] > $sectionsGrouped[$sectionId]['max_quantity']) {
                    $sectionsGrouped[$sectionId]['max_quantity'] = (int)$row['max_quantity'];
                }
            }
        }

        // Build sections with their associated items in the new preferred format
        $sectionsArray = [];
        foreach ($sectionsGrouped as $sectionData) {
            $sectionId = $sectionData['section_id'];
            
            // Get section items with details for this specific section
            $sectionItemsSql = "SELECT fsi.id, fsi.name, fsi.price, fisi.extra_price
                               FROM food_item_section_items fisi 
                               JOIN food_section_items fsi ON fisi.section_item_id = fsi.id 
                               WHERE fisi.item_id = :item_id AND fsi.section_id = :section_id";
            $sectionItemsStmt = $this->conn->prepare($sectionItemsSql);
            $sectionItemsStmt->execute([
                'item_id' => $result['id'],
                'section_id' => $sectionId
            ]);
            $sectionItemsDetails = $sectionItemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sectionsArray[] = [
                'section_id' => (int)$sectionId,
                'section_name' => $sectionData['section_name'],
                'required' => $sectionData['required'],
                'max_quantity' => $sectionData['max_quantity'],
                'items' => array_map(function($item) {
                    return [
                        'id' => (int)$item['id'],
                        'name' => $item['name'],
                        'price' => (float)$item['price'],
                        'extra_price' => (float)$item['extra_price']
                    ];
                }, $sectionItemsDetails)
            ];
        }
        $result['sections'] = $sectionsArray;

        // Note: section_items handling removed - now part of sections structure
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
    $status = 'active'; // Always set to active for new food sides

    // Otherwise insert
    $query = "INSERT INTO food_sides (store_id, name, price, status, created_at, updated_at) 
              VALUES (:store_id, :name, :price, :status, NOW(), NOW())";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':store_id', $data['store_id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':status', $status);
    $stmt->execute();

    return ['status' => 'success', 'message' => 'Food Side created successfully.'];
}


public function getFoodSideById($id)
{
    try {
        // Get the food side data with discount information
		$sql = "SELECT fs.id, fs.store_id, fs.name, fs.price, fs.status, fs.created_at, fs.updated_at,
                     d.id as discount_id, d.percentage, d.start_date as discount_start_date, d.end_date as discount_end_date, d.status as discount_status,
                     ROUND((fs.price - (fs.price * COALESCE(d.percentage, 0) / 100)), 2) as discount_price
                  FROM food_sides fs
                  LEFT JOIN discount_items di ON fs.id = di.item_id AND di.item_type = 'side'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = fs.store_id AND d.status = 'active' 
                      AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                      AND (d.end_date IS NULL OR d.end_date >= CURDATE())
                  WHERE fs.id = :id";
		$stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
		// Get total orders count for this side
		$orderQuery = "SELECT COUNT(DISTINCT oi.order_id) as total_orders 
					   FROM food_item_sides fis 
					   JOIN order_items oi ON fis.item_id = oi.item_id 
					   WHERE fis.side_id = :side_id";
		$orderStmt = $this->conn->prepare($orderQuery);
		$orderStmt->execute(['side_id' => $id]);
		$orderCount = $orderStmt->fetch(PDO::FETCH_ASSOC);
		
		// Add total_orders and convert numeric fields
		$result['total_orders'] = (int)$orderCount['total_orders'];
        $result['price'] = (float)$result['price'];
        
        // Debug: Log the raw result to see what's being returned
        error_log("Food Side By ID {$result['id']}: percentage={$result['percentage']}, discount_status={$result['discount_status']}, discount_id={$result['discount_id']}");
        
        // Only include discount fields if there's an active discount with percentage > 0
        if ($result['percentage'] && $result['percentage'] > 0 && $result['discount_status'] === 'active') {
            $result['discount_id'] = (int)$result['discount_id'];
            $result['percentage'] = (float)$result['percentage'];
        $result['discount_price'] = (float)$result['discount_price'];
            $result['discount_start_date'] = $result['discount_start_date'];
            $result['discount_end_date'] = $result['discount_end_date'];
        } else {
            // Remove discount fields if no active discount
            unset($result['discount_id']);
            unset($result['percentage']);
            unset($result['discount_price']);
            unset($result['discount_start_date']);
            unset($result['discount_end_date']);
        }
        // Always remove the discount_status field as it's internal
        unset($result['discount_status']);
        
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
            $query = "SELECT fs.id, fs.store_id, fs.name, fs.price, fs.status, fs.created_at, fs.updated_at,
                         d.id as discount_id, d.percentage, d.start_date as discount_start_date, d.end_date as discount_end_date, d.status as discount_status,
                         ROUND((fs.price - (fs.price * COALESCE(d.percentage, 0) / 100)), 2) as discount_price,
                         COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                  FROM food_sides fs
                  LEFT JOIN food_item_sides fis ON fs.id = fis.side_id
                  LEFT JOIN order_items oi ON fis.item_id = oi.item_id
                  LEFT JOIN discount_items di ON fs.id = di.item_id AND di.item_type = 'side'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = fs.store_id AND d.status = 'active' 
                      AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                      AND (d.end_date IS NULL OR d.end_date >= CURDATE())
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
            
            // Debug: Log the raw result to see what's being returned
            error_log("Food Side ID {$result['id']}: percentage={$result['percentage']}, discount_status={$result['discount_status']}, discount_id={$result['discount_id']}");
            
            // Only include discount fields if there's an active discount with percentage > 0
            if ($result['percentage'] && $result['percentage'] > 0 && $result['discount_status'] === 'active') {
                $result['discount_id'] = (int)$result['discount_id'];
                $result['percentage'] = (float)$result['percentage'];
            $result['discount_price'] = (float)$result['discount_price'];
                $result['discount_start_date'] = $result['discount_start_date'];
                $result['discount_end_date'] = $result['discount_end_date'];
            } else {
                // Remove discount fields if no active discount
                unset($result['discount_id']);
                unset($result['percentage']);
                unset($result['discount_price']);
                unset($result['discount_start_date']);
                unset($result['discount_end_date']);
            }
            // Always remove the discount_status field as it's internal
            unset($result['discount_status']);
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
    $query = "UPDATE food_sides SET 
              name = :name, 
              price = :price, 
              updated_at = NOW() 
              WHERE id = :id AND store_id = :store_id";
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
// READ All Sections by Store (with pagination + debug logs)
public function getAllFoodSectionsByStoreId($storeId, $limit = null, $offset = null)
{
    try {
        // Debug log
        error_log("[getAllFoodSectionsByStoreId] storeId=$storeId, limit=$limit, offset=$offset");
        
        // Base query
        $query = "SELECT fs.id, fs.store_id, fs.section_name, fs.max_quantity, fs.required, 
                         fs.price, fs.status, fs.created_at, fs.updated_at
                  FROM food_sections fs
                  WHERE fs.store_id = :store_id 
                    AND fs.status = 'active'
                  ORDER BY fs.id DESC";
    
        if ($limit !== null) {
            $query .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $query .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':store_id', (int)$storeId, PDO::PARAM_INT);
        $stmt->execute();

        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Attach items for each section
        foreach ($sections as &$section) {
            $section['max_quantity'] = (int)$section['max_quantity'];
            $section['required'] = (bool)$section['required'];
            $section['price'] = (float)$section['price'];

            $itemQuery = "SELECT id, name, price, status 
                          FROM food_section_items 
                          WHERE section_id = :section_id AND status = 'active'
                          ORDER BY id ASC";
            $itemStmt = $this->conn->prepare($itemQuery);
            $itemStmt->execute(['section_id' => $section['id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize item data
            foreach ($items as &$item) {
                $item['price'] = (float)$item['price'];
            }

            $section['items'] = $items;
        }

        return $sections;

    } catch (PDOException $e) {
        error_log("[getAllFoodSectionsByStoreId] ERROR: " . $e->getMessage());
        return [];
    }
}


// Count total food sections by store (with debug log)
public function countFoodSectionsByStoreId($storeId)
{
    try {
        $query = "SELECT COUNT(*) 
                  FROM food_sections 
                  WHERE store_id = :store_id 
                    AND status = 'active'";
        error_log("SQL (count): " . $query);
        error_log("Param store_id: " . $storeId);
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':store_id', (int)$storeId, PDO::PARAM_INT);
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();
        
        error_log("Count result: " . $count);
        return $count;
    } catch (PDOException $e) {
        error_log("countFoodSectionsByStoreId error: " . $e->getMessage());
        return 0;
    }
}



// GET Single Section by ID
public function getFoodSectionById($id)
{
    try {
        $id = (int) $id; // sanitize
        
        $query = "SELECT fs.id, fs.store_id, fs.section_name as name, fs.max_quantity, 
                         fs.required, fs.price, fs.status, fs.created_at, fs.updated_at,
                         d.percentage,
                         d.id as discount_id,
                         d.start_date as discount_start_date,
                         d.end_date as discount_end_date
                  FROM food_sections fs
                  LEFT JOIN discount_items di 
                         ON fs.id = di.item_id AND di.item_type = 'section'
                  LEFT JOIN discounts d 
                         ON di.discount_id = d.id 
                        AND d.status = 'active' 
                      AND NOW() BETWEEN d.start_date AND d.end_date
                  WHERE fs.id = :id AND fs.status = 'active'";
        
    $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            return null;
        }

        // Convert numeric/boolean values
        $section['price'] = (float)$section['price'];
        $section['max_quantity'] = (int)$section['max_quantity'];
        $section['required'] = (bool)$section['required'];
        
        // Attach discount ONLY if present
        if ($section['discount_id']) {
            $section['percentage'] = (float)$section['percentage'];
            $section['discount_id'] = (int)$section['discount_id'];
        } else {
            unset($section['percentage']);
            unset($section['discount_id']);
            unset($section['discount_start_date']);
            unset($section['discount_end_date']);
        }

        return $section;
    } catch (PDOException $e) {
        error_log("DB error in getFoodSectionById: " . $e->getMessage());
        return null;
    }
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
                // Check for existing items to prevent duplicates
                $existingItemsQuery = "SELECT name FROM food_section_items WHERE section_id = :section_id AND status = 'active'";
                $existingStmt = $this->conn->prepare($existingItemsQuery);
                $existingStmt->execute(['section_id' => $data['section_id']]);
                $existingItems = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Check for duplicates
                $duplicateItems = [];
                foreach ($data['items'] as $item) {
                    if (in_array($item['name'], $existingItems)) {
                        $duplicateItems[] = $item['name'];
                    }
                }
                
                if (!empty($duplicateItems)) {
                    $duplicateNames = implode(', ', $duplicateItems);
                    throw new \Exception("The following items already exist in this section: {$duplicateNames}. Please use different names.");
                }
                
                // Insert the new items
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
        
        // Check for duplicate item name error
        if ($e->getCode() == 23000 && strpos($e->getMessage(), 'unique_section_item_name') !== false) {
            // Extract the duplicate item name from the error message
            if (preg_match('/Duplicate entry \'[^-]+-([^\']+)\'/', $e->getMessage(), $matches)) {
                $duplicateItemName = $matches[1];
                throw new \Exception("Item with name '{$duplicateItemName}' already exists in this section. Please use a different name.");
            } else {
                throw new \Exception("An item with this name already exists in this section. Please use a different name.");
            }
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
                   ROUND((fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)), 2) as calculated_discount_price,
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
                   ROUND((fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)), 2) as calculated_discount_price,
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

// New method to handle sections with items in the preferred format
private function createFoodItemSectionsWithItems($foodItemId, $sectionsArray)
{
    foreach ($sectionsArray as $sectionData) {
        // Create section configuration for this specific section
        $configSql = "INSERT INTO food_item_sections_config (item_id, required, max_quantity) 
                      VALUES (:item_id, :required, :max_quantity)";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute([
            'item_id' => $foodItemId,
            'required' => $sectionData['required'] ?? false,
            'max_quantity' => $sectionData['max_quantity'] ?? 0
        ]);

        // Create the section relationship
        $sectionSql = "INSERT INTO food_item_sections (item_id, section_id) VALUES (:item_id, :section_id)";
        $sectionStmt = $this->conn->prepare($sectionSql);
        $sectionStmt->execute([
            'item_id' => $foodItemId,
            'section_id' => $sectionData['section_id']
        ]);

        // Create section items relationships if item_ids are provided
        if (!empty($sectionData['item_ids']) && is_array($sectionData['item_ids'])) {
            $sectionItemSql = "INSERT INTO food_item_section_items (item_id, section_item_id, extra_price) VALUES (:item_id, :section_item_id, :extra_price)";
            $sectionItemStmt = $this->conn->prepare($sectionItemSql);

            foreach ($sectionData['item_ids'] as $itemId) {
                $extraPrice = 0;
                
                // Handle both simple ID format and object format
                if (is_array($itemId) && isset($itemId['id'])) {
                    $extraPrice = $itemId['extra_price'] ?? 0;
                    $itemId = $itemId['id'];
                }

                $sectionItemStmt->execute([
                    'item_id' => $foodItemId,
                    'section_item_id' => $itemId,
                    'extra_price' => $extraPrice
                ]);
            }
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
        // Check if it's the new format (array of objects with section_id, required, max_quantity, item_ids)
        if (isset($sectionsArray[0]) && is_array($sectionsArray[0]) && isset($sectionsArray[0]['section_id'])) {
            // New format: Handle each section with its own configuration
            foreach ($sectionsArray as $section) {
                if (isset($section['section_id']) && is_numeric($section['section_id'])) {
                    // Create section config for this specific section
                    $configSql = "INSERT INTO food_item_sections_config (item_id, section_id, required, max_quantity, created_at) VALUES (:item_id, :section_id, :required, :max_quantity, NOW())";
                    $configStmt = $this->conn->prepare($configSql);
                    $configStmt->execute([
                        'item_id' => $foodItemId,
                        'section_id' => $section['section_id'],
                        'required' => $section['required'] ? 1 : 0,
                        'max_quantity' => $section['max_quantity']
                    ]);

                    // Link the section to the food item
                    $sql = "INSERT INTO food_item_sections (item_id, section_id, created_at) VALUES (:item_id, :section_id, NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([
                        'item_id' => $foodItemId,
                        'section_id' => $section['section_id']
                    ]);

                    // Handle section items if provided
                    if (isset($section['item_ids']) && is_array($section['item_ids']) && !empty($section['item_ids'])) {
                        // Create section items config for this specific section
                        $sectionItemsConfigSql = "INSERT INTO food_item_section_items_config (item_id, section_id, required, max_quantity, created_at) VALUES (:item_id, :section_id, :required, :max_quantity, NOW())";
                        $sectionItemsConfigStmt = $this->conn->prepare($sectionItemsConfigSql);
                        $sectionItemsConfigStmt->execute([
                            'item_id' => $foodItemId,
                            'section_id' => $section['section_id'],
                            'required' => $section['required'] ? 1 : 0,
                            'max_quantity' => $section['max_quantity']
                        ]);

                        // Link specific section items
                        foreach ($section['item_ids'] as $itemId) {
                            if (is_numeric($itemId)) {
                                $itemSql = "INSERT INTO food_item_section_items (item_id, section_id, section_item_id, created_at) VALUES (:item_id, :section_id, :section_item_id, NOW())";
                                $itemStmt = $this->conn->prepare($itemSql);
                                $itemStmt->execute([
                                    'item_id' => $foodItemId,
                                    'section_id' => $section['section_id'],
                                    'section_item_id' => $itemId
                                ]);
                            }
                        }
                    }
                }
            }
        } else {
            // Old format: Handle backward compatibility
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

    // Create food item section items with config (structured format)
    private function createFoodItemSectionItemsWithConfig($foodItemId, $sectionItemsData)
    {
        // First, create the food item section items configuration
        $configSql = "INSERT INTO food_item_section_items_config (item_id, required, max_quantity) 
                      VALUES (:item_id, :required, :max_quantity)";
        $configStmt = $this->conn->prepare($configSql);
        $configStmt->execute([
            'item_id' => $foodItemId,
            'required' => $sectionItemsData['required'] ?? false,
            'max_quantity' => $sectionItemsData['max_quantity'] ?? 0
        ]);

        // Then, create the section item relationships
        if (!empty($sectionItemsData['items']) && is_array($sectionItemsData['items'])) {
            $sectionItemSql = "INSERT INTO food_item_section_items (item_id, section_item_id, extra_price) VALUES (:item_id, :section_item_id, :extra_price)";
            $sectionItemStmt = $this->conn->prepare($sectionItemSql);

            foreach ($sectionItemsData['items'] as $sectionItem) {
                $sectionItemId = is_array($sectionItem) ? $sectionItem['id'] : $sectionItem;
                $extraPrice = is_array($sectionItem) ? ($sectionItem['extra_price'] ?? 0) : 0;

                $sectionItemStmt->execute([
                    'item_id' => $foodItemId,
                    'section_item_id' => $sectionItemId,
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

    // ========================================
    // FOOD SECTION ITEMS MANAGEMENT METHODS
    // ========================================

    // CREATE Section Item
    public function createSectionItem($data)
    {
        try {
            // Check if item with same name already exists in this section
            $checkQuery = "SELECT COUNT(*) FROM food_section_items WHERE section_id = :section_id AND name = :name AND status = 'active'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([
                ':section_id' => $data['section_id'],
                ':name' => $data['name']
            ]);
            
            if ($checkStmt->fetchColumn() > 0) {
                throw new \Exception("An item with name '{$data['name']}' already exists in this section. Please use a different name.");
            }

            $query = "INSERT INTO food_section_items (section_id, name, price, status) VALUES (:section_id, :name, :price, 'active')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':section_id' => $data['section_id'],
                ':name' => $data['name'],
                ':price' => $data['price']
            ]);

            $itemId = $this->conn->lastInsertId();
            
            // Return the created item
            return $this->getSectionItemById($itemId);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'unique_section_item_name') !== false) {
                throw new \Exception("An item with this name already exists in this section. Please use a different name.");
            }
            throw new \Exception("Error creating section item: " . $e->getMessage());
        }
    }

    // UPDATE Section Item
    public function updateSectionItem($data)
    {
        try {
            // Check if item exists
            $existingItem = $this->getSectionItemById($data['item_id']);
            if (!$existingItem) {
                throw new \Exception("Section item not found.");
            }

            // Check if another item with same name exists in this section (excluding current item)
            if (isset($data['name'])) {
                $checkQuery = "SELECT COUNT(*) FROM food_section_items WHERE section_id = :section_id AND name = :name AND id != :item_id AND status = 'active'";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->execute([
                    ':section_id' => $existingItem['section_id'],
                    ':name' => $data['name'],
                    ':item_id' => $data['item_id']
                ]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    throw new \Exception("An item with name '{$data['name']}' already exists in this section. Please use a different name.");
                }
            }

            $query = "UPDATE food_section_items SET ";
            $params = [];
            
            if (isset($data['name'])) {
                $query .= "name = :name, ";
                $params[':name'] = $data['name'];
            }
            
            if (isset($data['price'])) {
                $query .= "price = :price, ";
                $params[':price'] = $data['price'];
            }
            
            $query .= "updated_at = NOW() WHERE id = :item_id";
            $params[':item_id'] = $data['item_id'];
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            // Return the updated item
            return $this->getSectionItemById($data['item_id']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'unique_section_item_name') !== false) {
                throw new \Exception("An item with this name already exists in this section. Please use a different name.");
            }
            throw new \Exception("Error updating section item: " . $e->getMessage());
        }
    }

    // DELETE Section Item
    public function deleteSectionItem($itemId)
    {
        $query = "DELETE FROM food_section_items WHERE id = :item_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $itemId);
        return $stmt->execute();
    }

    // ACTIVATE Section Item
    public function activateSectionItem($itemId)
    {
        $query = "UPDATE food_section_items SET status = 'active', updated_at = NOW() WHERE id = :item_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $itemId);
        return $stmt->execute();
    }

    // DEACTIVATE Section Item
    public function deactivateSectionItem($itemId)
    {
        $query = "UPDATE food_section_items SET status = 'inactive', updated_at = NOW() WHERE id = :item_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $itemId);
        return $stmt->execute();
    }

    // BULK DELETE Section Items
    public function bulkDeleteSectionItems($itemIds)
    {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $query = "DELETE FROM food_section_items WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($itemIds);
    }

    // BULK ACTIVATE Section Items
    public function bulkActivateSectionItems($itemIds)
    {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $query = "UPDATE food_section_items SET status = 'active', updated_at = NOW() WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($itemIds);
    }

    // BULK DEACTIVATE Section Items
    public function bulkDeactivateSectionItems($itemIds)
    {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $query = "UPDATE food_section_items SET status = 'inactive', updated_at = NOW() WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($itemIds);
    }

    // GET Section Item by ID
    public function getSectionItemById($itemId)
    {
        $query = "SELECT * FROM food_section_items WHERE id = :item_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // GET Section Items by Section ID
    public function getSectionItemsBySectionId($sectionId)
    {
        $query = "SELECT * FROM food_section_items WHERE section_id = :section_id ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':section_id', $sectionId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // CHECK if Section Item belongs to User's Store
    public function checkSectionItemOwnership($itemId, $storeId)
    {
        $query = "SELECT COUNT(*) FROM food_section_items fsi 
                  JOIN food_sections fs ON fsi.section_id = fs.id 
                  WHERE fsi.id = :item_id AND fs.store_id = :store_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':item_id' => $itemId,
            ':store_id' => $storeId
        ]);
        return $stmt->fetchColumn() > 0;
    }

    // CHECK if Section Items belong to User's Store (for bulk operations)
    public function checkSectionItemsOwnership($itemIds, $storeId)
    {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $query = "SELECT COUNT(*) FROM food_section_items fsi 
                  JOIN food_sections fs ON fsi.section_id = fs.id 
                  WHERE fsi.id IN ($placeholders) AND fs.store_id = ?";
        $stmt = $this->conn->prepare($query);
        
        $params = array_merge($itemIds, [$storeId]);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() == count($itemIds);
    }

    // GET all Section Items in a Store with pagination
    public function getAllSectionItemsInStore($storeId, $page = 1, $limit = 10, $sectionId = null)
    {
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause based on whether section_id is provided
        $whereClause = "fs.store_id = :store_id";
        $params = [':store_id' => $storeId];
        
        if ($sectionId !== null) {
            $whereClause .= " AND fsi.section_id = :section_id";
            $params[':section_id'] = $sectionId;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM food_section_items fsi 
                      JOIN food_sections fs ON fsi.section_id = fs.id 
                      WHERE " . $whereClause;
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetchColumn();
        
        // Get paginated results with discount information and total orders
        $query = "SELECT fsi.*, fs.section_name, 
                         d.id as discount_id, d.percentage, d.start_date as discount_start_date, d.end_date as discount_end_date, d.status as discount_status,
                         ROUND((fsi.price - (fsi.price * COALESCE(d.percentage, 0) / 100)), 2) as discount_price,
                         COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                  FROM food_section_items fsi 
                  JOIN food_sections fs ON fsi.section_id = fs.id 
                  LEFT JOIN discount_items di ON fsi.id = di.item_id AND di.item_type = 'food_section_item'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = fs.store_id AND d.status = 'active' 
                      AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                      AND (d.end_date IS NULL OR d.end_date >= CURDATE())
                  LEFT JOIN order_items oi ON fsi.id = oi.item_id
                  WHERE " . $whereClause . " 
                  GROUP BY fsi.id
                  ORDER BY fsi.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process discount fields and total orders for each item
        foreach ($items as &$item) {
            $item['price'] = (float)$item['price'];
            $item['total_orders'] = (int)$item['total_orders'];
            
            // Only include discount fields if there's an active discount with percentage > 0
            if ($item['percentage'] && $item['percentage'] > 0 && $item['discount_status'] === 'active') {
                $item['discount_id'] = (int)$item['discount_id'];
                $item['percentage'] = (float)$item['percentage'];
                $item['discount_price'] = (float)$item['discount_price'];
                $item['discount_start_date'] = $item['discount_start_date'];
                $item['discount_end_date'] = $item['discount_end_date'];
            } else {
                // Remove discount fields if no active discount
                unset($item['discount_id']);
                unset($item['percentage']);
                unset($item['discount_price']);
                unset($item['discount_start_date']);
                unset($item['discount_end_date']);
            }
            // Always remove the discount_status field as it's internal
            unset($item['discount_status']);
        }
        
        return $items;
    }

    // GET Section Item by ID with section details
    public function getSectionItemByIdWithDetails($itemId)
    {
        $query = "SELECT fsi.*, fs.section_name, fs.store_id,
                         d.id as discount_id, d.percentage, d.start_date as discount_start_date, d.end_date as discount_end_date, d.status as discount_status,
                         ROUND((fsi.price - (fsi.price * COALESCE(d.percentage, 0) / 100)), 2) as discount_price,
                         COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                  FROM food_section_items fsi 
                  JOIN food_sections fs ON fsi.section_id = fs.id 
                  LEFT JOIN discount_items di ON fsi.id = di.item_id AND di.item_type = 'food_section_item'
                  LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = fs.store_id AND d.status = 'active' 
                      AND (d.start_date IS NULL OR d.start_date <= CURDATE()) 
                      AND (d.end_date IS NULL OR d.end_date >= CURDATE())
                  LEFT JOIN order_items oi ON fsi.id = oi.item_id
                  WHERE fsi.id = :item_id
                  GROUP BY fsi.id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['price'] = (float)$result['price'];
            $result['total_orders'] = (int)$result['total_orders'];
            
            // Only include discount fields if there's an active discount with percentage > 0
            if ($result['percentage'] && $result['percentage'] > 0 && $result['discount_status'] === 'active') {
                $result['discount_id'] = (int)$result['discount_id'];
                $result['percentage'] = (float)$result['percentage'];
                $result['discount_price'] = (float)$result['discount_price'];
                $result['discount_start_date'] = $result['discount_start_date'];
                $result['discount_end_date'] = $result['discount_end_date'];
            } else {
                // Remove discount fields if no active discount
                unset($result['discount_id']);
                unset($result['percentage']);
                unset($result['discount_price']);
                unset($result['discount_start_date']);
                unset($result['discount_end_date']);
            }
            // Always remove the discount_status field as it's internal
            unset($result['discount_status']);
        }
        
        return $result;
    }

    // COUNT Section Items by Store ID
    public function countSectionItemsByStoreId($storeId, $sectionId = null)
    {
        // Build WHERE clause based on whether section_id is provided
        $whereClause = "fs.store_id = :store_id";
        $params = [':store_id' => $storeId];
        
        if ($sectionId !== null) {
            $whereClause .= " AND fsi.section_id = :section_id";
            $params[':section_id'] = $sectionId;
        }
        
        $query = "SELECT COUNT(*) FROM food_section_items fsi 
                  JOIN food_sections fs ON fsi.section_id = fs.id 
                  WHERE " . $whereClause;
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}