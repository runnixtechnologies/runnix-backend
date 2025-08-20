<?php
namespace Controller;

use Model\FoodItem;
use Model\Store;
use Config\JwtHandler;
use Config\Database;

class FoodItemController
{
    private $foodItem;
    private $storeModel;
    private $conn;

    public function __construct()
    {
        $this->foodItem = new FoodItem();
         $this->storeModel = new Store();
        $this->conn = (new Database())->getConnection();
    }

   public function create($data,$user)
{
    $photo = null;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/x-png'
        ];

        $fileType = $_FILES['photo']['type'];
        $fileSize = $_FILES['photo']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(415);
            return ["status" => "error", "message" => "Unsupported image format."];
        }

        if ($fileSize > 3 * 1024 * 1024) { // 3MB
            http_response_code(413);
            return ["status" => "error", "message" => "Image exceeds max size of 3MB."];
        }

        $uploadDir = __DIR__ . '/../../uploads/food-items/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('item_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to upload image."];
        }

       $photo = 'https://api.runnix.africa/uploads/food-items/' . $filename;
    }

    // Attach photo to data
    $data['photo'] = $photo;

    // Validate required fields
    if (!isset($data['name']) || empty(trim($data['name']))) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food item name is required'];
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Valid price is required'];
    }

    // Extract user_id from authenticated user
    if (!isset($user['user_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'User ID not found in authentication token'];
    }
    $data['user_id'] = $user['user_id'];

    // Extract store_id from authenticated user (for merchants)
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    $data['store_id'] = $user['store_id'];

    // Validate store exists
    if (!$this->storeModel->storeIdExists($data['store_id'])) {
        http_response_code(400);
    return ['status' => 'error', 'message' => 'Invalid store_id. Store does not exist.'];
}

    // Validate category_id is required
    if (!isset($data['category_id']) || empty($data['category_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Category ID is required. Please select a category for this food item.'];
    }

    // Validate category exists and is active
    $categoryCheck = $this->conn->prepare("
        SELECT c.id FROM categories c 
        WHERE c.id = :category_id AND (c.status = 'active' OR c.status = '1')
    ");
    $categoryCheck->execute([
        'category_id' => $data['category_id']
    ]);
    
    if ($categoryCheck->fetchColumn() == 0) {
        // Temporarily log the issue for debugging
        error_log("Category validation failed for category_id: " . $data['category_id']);
        
        // Check if category exists at all
        $categoryExistsCheck = $this->conn->prepare("SELECT id, name, status FROM categories WHERE id = :category_id");
        $categoryExistsCheck->execute(['category_id' => $data['category_id']]);
        $categoryInfo = $categoryExistsCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($categoryInfo) {
            error_log("Category exists but status is: " . $categoryInfo['status']);
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Category exists but is not active. Status: ' . $categoryInfo['status'] . '. Expected: active or 1'];
        } else {
            error_log("Category does not exist");
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Category ID ' . $data['category_id'] . ' does not exist in database.'];
        }
    }

    // Parse JSON strings to arrays if needed
    if (isset($data['sides']) && is_string($data['sides'])) {
        $data['sides'] = json_decode($data['sides'], true);
    }
    if (isset($data['packs']) && is_string($data['packs'])) {
        $data['packs'] = json_decode($data['packs'], true);
    }
    if (isset($data['sections']) && is_string($data['sections'])) {
        $data['sections'] = json_decode($data['sections'], true);
    }

    // Validate and normalize sides data if provided
    if (isset($data['sides']) && is_array($data['sides'])) {
        // Check if it's the old format (object with required, max_quantity, items)
        if (isset($data['sides']['required']) || isset($data['sides']['max_quantity']) || isset($data['sides']['items'])) {
            // Old format - validate as before
            if (isset($data['sides']['required']) && !is_bool($data['sides']['required'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Sides required must be a boolean'];
            }

        } else {
            // New format - array of objects or simple IDs
            foreach ($data['sides'] as $side) {
                if (is_numeric($side)) {
                    // Simple ID format - convert to object format
                    continue;
                } elseif (is_array($side) && isset($side['id']) && is_numeric($side['id'])) {
                    // Object format with id
                    continue;
                } else {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Each side must be either a valid ID number or an object with a valid id'];
                }
            }
        }
    }

    // Validate and normalize packs data if provided
    if (isset($data['packs']) && is_array($data['packs'])) {
        // Check if it's the old format (object with required, max_quantity, items)
        if (isset($data['packs']['required']) || isset($data['packs']['max_quantity']) || isset($data['packs']['items'])) {
            // Old format - validate as before
            if (isset($data['packs']['required']) && !is_bool($data['packs']['required'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Packs required must be a boolean'];
            }

        } else {
            // New format - array of objects or simple IDs
            foreach ($data['packs'] as $pack) {
                if (is_numeric($pack)) {
                    // Simple ID format - convert to object format
                    continue;
                } elseif (is_array($pack) && isset($pack['id']) && is_numeric($pack['id'])) {
                    // Object format with id
                    continue;
                } else {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Each pack must be either a valid ID number or an object with a valid id'];
                }
            }
        }
    }

    // Validate and normalize sections data if provided
    if (isset($data['sections']) && is_array($data['sections'])) {
        // Check if it's the old format (object with required, max_quantity, items)
        if (isset($data['sections']['required']) || isset($data['sections']['max_quantity']) || isset($data['sections']['items'])) {
            // Old format - validate as before
            if (isset($data['sections']['required']) && !is_bool($data['sections']['required'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Sections required must be a boolean'];
            }

        } else {
            // New format - array of objects with enhanced section support
            foreach ($data['sections'] as $section) {
                if (!is_array($section)) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Each section must be an object'];
                }
                
                // Support both simple section ID and enhanced format with selected items
                if (isset($section['id']) && is_numeric($section['id'])) {
                    // Simple section ID format
                    if (isset($section['selected_items']) && is_array($section['selected_items'])) {
                        // Enhanced format with selected items
                        foreach ($section['selected_items'] as $itemId) {
                            if (!is_numeric($itemId)) {
                                http_response_code(400);
                                return ['status' => 'error', 'message' => 'Each selected item ID must be a valid number'];
                            }
                        }
                    }
                } else {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Each section must have a valid id'];
                }
            }
        }
    }



    $result = $this->foodItem->createWithOptions($data);
    if ($result && isset($result['status']) && $result['status'] === 'success') {
        http_response_code(201); // Created
        return $result;
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to create food item'];
    }
}


   public function update($data, $user)
{
    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food item ID is required'];
    }

    // Check if item exists
    if (!$this->foodItem->itemExists($data['id'])) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food item does not exist in the DB'];
    }

    // Authorization check - verify the food item belongs to the user's store
    $existingItem = $this->foodItem->getById($data['id']);
    if (!$existingItem) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food item not found'];
    }
    
    if ($existingItem['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to update this item."];
    }

    // Handle photo upload if new photo is provided
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/x-png'
        ];

        $fileType = $_FILES['photo']['type'];
        $fileSize = $_FILES['photo']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(415);
            return ["status" => "error", "message" => "Unsupported image format."];
        }

        if ($fileSize > 3 * 1024 * 1024) { // 3MB
            http_response_code(413);
            return ["status" => "error", "message" => "Image exceeds max size of 3MB."];
        }

        $uploadDir = __DIR__ . '/../../uploads/food-items/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('item_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to upload image."];
        }

        $photo = 'https://api.runnix.africa/uploads/food-items/' . $filename;
    }

    // If new photo is uploaded, update the photo field
    if ($photo) {
        $data['photo'] = $photo;
    }

    // Validate basic fields if provided
    if (isset($data['name']) && empty(trim($data['name']))) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food item name cannot be empty'];
    }

    if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Price must be a valid positive number'];
    }

    // Validate category_id if provided
    if (isset($data['category_id']) && !empty($data['category_id'])) {
        // Get the food item to check its store_id
        $existingItem = $this->foodItem->getById($data['id']);
        if (!$existingItem) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'Food item not found'];
        }

        // Validate category exists, is active, and belongs to the merchant's store type
        $categoryCheck = $this->conn->prepare("
            SELECT c.id FROM categories c 
            JOIN store_types st ON c.store_type_id = st.id 
            JOIN stores s ON s.store_type_id = st.id 
            WHERE c.id = :category_id AND s.id = :store_id AND c.status = 'active'
        ");
        $categoryCheck->execute([
            'category_id' => $data['category_id'],
            'store_id' => $existingItem['store_id']
        ]);
        
        if ($categoryCheck->fetchColumn() == 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid category ID. Please select an active category that belongs to your store type.'];
        }
    }

    // Validate sides data if provided
    if (isset($data['sides']) && is_array($data['sides'])) {
        if (isset($data['sides']['required']) && !is_bool($data['sides']['required'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Sides required must be a boolean'];
        }
        if (isset($data['sides']['max_quantity']) && (!is_numeric($data['sides']['max_quantity']) || $data['sides']['max_quantity'] < 0)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Sides max quantity must be a non-negative number'];
        }
    }

    // Validate packs data if provided
    if (isset($data['packs']) && is_array($data['packs'])) {
        if (isset($data['packs']['required']) && !is_bool($data['packs']['required'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Packs required must be a boolean'];
        }
        if (isset($data['packs']['max_quantity']) && (!is_numeric($data['packs']['max_quantity']) || $data['packs']['max_quantity'] < 0)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Packs max quantity must be a non-negative number'];
        }
    }

    // Validate sections data if provided
    if (isset($data['sections']) && is_array($data['sections'])) {
        if (isset($data['sections']['required']) && !is_bool($data['sections']['required'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Sections required must be a boolean'];
        }
        if (isset($data['sections']['max_quantity']) && (!is_numeric($data['sections']['max_quantity']) || $data['sections']['max_quantity'] < 0)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Sections max quantity must be a non-negative number'];
        }
    }

    // Use the enhanced update method
    $result = $this->foodItem->updateWithOptions($data);
    if ($result && isset($result['status']) && $result['status'] === 'success') {
        http_response_code(200); // OK
        return $result;
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to update food item'];
    }
}


    public function delete($id,$user)
    {

         // Authorization check
        if (!$this->userOwnsItem($id, $user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to delete this item."];
     }
        $result = $this->foodItem->delete($id);
        if ($result) {
            http_response_code(200); // OK
            return ['status' => 'success', 'message' => 'Food item deleted successfully'];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Food item not found or not deleted'];
        }
    }

    private function userOwnsItem($id, $userId)
{
    if (!$userId || !is_numeric($userId)) {
        return false;
    }

    // Check if the food item belongs to the user's store
    $existingItem = $this->foodItem->getById($id);
    if (!$existingItem) {
        return false;
    }
    
    // Get the user's store_id
    $store = $this->storeModel->getStoreByUserId($userId);
    if (!$store) {
        return false;
    }
    
    return $existingItem['store_id'] == $store['id'];
}

    public function getByItemId($id,$user)
    {
        $result = $this->foodItem->getByItemId($id);
        if ($result) {
            http_response_code(200); // OK
            return ['status' => 'success', 'data' => $result];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Food item not found'];
        }
    }

    public function getAllFoodItems($user, $page = 1, $limit = 10)
{
    // Check if user is merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403); // Forbidden
        return ["status" => "error", "message" => "Access denied. Only merchants can fetch food items."];
    }

    // Get store for this merchant user
    $store = $this->storeModel->getStoreByUserId($user['user_id']);

    if (!$store) {
        http_response_code(404); // Not Found
        return ["status" => "error", "message" => "Store not found for this user."];
    }

    $storeId = $store['id'];
    $offset = ($page - 1) * $limit;

    // Get food items using the store_id with pagination
    $foodItems = $this->foodItem->getAllByStoreIdPaginated($storeId, $limit, $offset);
    $totalCount = $this->foodItem->countFoodItemsByStoreId($storeId);

    http_response_code(200); // OK
    return [
        "status" => "success", 
        "data" => $foodItems,
        "meta" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalCount,
            "total_pages" => ceil($totalCount / $limit),
            "has_next" => ($page * $limit) < $totalCount,
            "has_prev" => $page > 1
        ]
    ];
}


public function getAllFoodItemsByStoreId($storeId, $user, $page = 1, $limit = 10)
{
    // Check if store exists
    $store = $this->storeModel->getStoreById($storeId);
    if (!$store) {
        http_response_code(404); // Not Found
        return ["status" => "error", "message" => "Store not found."];
    }

    // If user is merchant, ensure they can only access their own store
    if ($user['role'] === 'merchant' && $store['user_id'] != $user['user_id']) {
        http_response_code(403); // Forbidden
        return ["status" => "error", "message" => "Access denied. You can only view your own store's food items."];
    }

    $offset = ($page - 1) * $limit;

    // Fetch food items with pagination
    $foodItems = $this->foodItem->getAllByStoreIdPaginated($storeId, $limit, $offset);
    $totalCount = $this->foodItem->countFoodItemsByStoreId($storeId);
    
    // Always return success with data (empty array if no items found)
    http_response_code(200);
    return [
        "status" => "success", 
        "data" => $foodItems ?: [],
        "meta" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalCount,
            "total_pages" => ceil($totalCount / $limit),
            "has_next" => ($page * $limit) < $totalCount,
            "has_prev" => $page > 1
        ]
    ];
}

// CREATE Food Side
public function createFoodSide($data, $user)
{
    // Debug: Log the received data
    error_log("createFoodSide: Received data: " . print_r($data, true));
    error_log("createFoodSide: User data: " . print_r($user, true));
    
    // Ensure store_id
    if (!isset($data['store_id']) || empty($data['store_id'])) {
        $data['store_id'] = $user['store_id'];
    }

    // Validate name
    if (!isset($data['name']) || empty(trim($data['name']))) {
        error_log("createFoodSide: Name validation failed. Data keys: " . implode(', ', array_keys($data)));
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food side name is required'];
    }

    // Validate price
    if (!isset($data['price']) || empty($data['price'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food side price is required'];
    }

    // Validate discount (optional)
    if (isset($data['discount'])) {
        if (!is_numeric($data['discount']) || $data['discount'] < 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Discount must be a non-negative number'];
        }
    }

    // Validate percentage (optional)
    if (isset($data['percentage'])) {
        if (!is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Percentage must be between 0 and 100'];
        }
    }

    $result = $this->foodItem->createFoodSide($data);

    // Check result returned by the model
    if (isset($result['status']) && $result['status'] === 'error') {
        // Model detected error (like duplicate name)
        return $result;  // Already has correct message & status
    }

    // Otherwise, model inserted successfully
    http_response_code(201);
    return ['status' => 'success', 'message' => 'Food side created successfully'];
}


public function getFoodSideById($id, $user)
{
    // Get store_id from database if not in JWT token
    $storeId = $user['store_id'] ?? null;
    if (!$storeId) {
        // Fetch store from database using user_id
        $store = $this->storeModel->getStoreByUserId($user['user_id']);
        if (!$store) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store not found for user'];
        }
        $storeId = $store['id'];
    }
    
    error_log("getFoodSideById: Looking for food side ID: $id, Store ID: $storeId");
    
    $side = $this->foodItem->getFoodSideById($id);
    
    if ($side) {
        error_log("getFoodSideById: Found food side - Store ID: " . $side['store_id']);
        
        // Check if the food side belongs to the user's store
        if ($side['store_id'] != $storeId) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Unauthorized to access this food side'];
        }
        
        http_response_code(200); // OK
        return ['status' => 'success', 'data' => $side];
    } else {
        error_log("getFoodSideById: Food side not found for ID: $id");
        http_response_code(404); // Not Found
        return ['status' => 'error', 'message' => 'Food Side not found'];
    }
}

// READ All Sides by Store
public function getAllFoodSidesByStoreId($user, $page = 1, $limit = 10)
{
    // Extract store_id from authenticated user
    $store_id = $user['store_id'];
    
    if (!$store_id) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found for user'];
    }

    $offset = ($page - 1) * $limit;

    // Get total count for pagination
    $totalCount = $this->foodItem->getFoodSidesCountByStoreId($store_id);
    
    // Get paginated results
    $result = $this->foodItem->getAllFoodSidesByStoreId($store_id, $limit, $offset);
    
    http_response_code(200);
    return [
        'status' => 'success', 
        'data' => $result,
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'total_pages' => ceil($totalCount / $limit),
            'has_next' => ($page * $limit) < $totalCount,
            'has_prev' => $page > 1
        ]
    ];
}

public function deactivateBulkFoodSides($ids, $user)
{
    if (empty($ids) || !is_array($ids)) {
        return ['status' => 'error', 'message' => 'Invalid input: IDs must be a non-empty array.'];
    }

    $result = $this->foodItem->bulkUpdateFoodSideStatus($ids, 'inactive');

    if ($result) {
        return ['status' => 'success', 'message' => 'Food sides deactivated successfully.'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to deactivate food sides.'];
    }
}

public function activateBulkFoodSides($ids, $user)
{
    if (empty($ids) || !is_array($ids)) {
        return ['status' => 'error', 'message' => 'Invalid input: IDs must be a non-empty array.'];
    }

    $result = $this->foodItem->bulkUpdateFoodSideStatus($ids, 'active');

    if ($result) {
        return ['status' => 'success', 'message' => 'Food sides activated successfully.'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to activate food sides.'];
    }
}

// UPDATE Food Side
public function updateFoodSide($data, $user)
{
    // Validate discount (optional)
    if (isset($data['discount'])) {
        if (!is_numeric($data['discount']) || $data['discount'] < 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Discount must be a non-negative number'];
        }
    }

    // Validate percentage (optional)
    if (isset($data['percentage'])) {
        if (!is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Percentage must be between 0 and 100'];
        }
    }

    $result = $this->foodItem->updateFoodSide($data);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food side updated successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food side not found or not updated'];
    }
}

// DELETE Food Side
public function deleteFoodSide($id,$user)
{
    $result = $this->foodItem->deleteFoodSide($id);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food side deleted successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food side not found or not deleted'];
    }
}

// File: app/controllers/FoodSideController.php

public function activateFoodSide($id, $user)
{
    // Fetch the food side to verify ownership
    $foodSide = $this->foodItem->getFoodSideById($id);
    
    if (!$foodSide) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food side not found'];
    }

    // Check if the user owns the store
    if ($foodSide['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized'];
    }

    $result = $this->foodItem->updateFoodSideStatus($id, 'active');

    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food side activated'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to update food side'];
    }
}


public function deactivateFoodSide($id, $user)
{
    // Fetch the food side to verify ownership
    $foodSide = $this->foodItem->getFoodSideById($id);
    
    if (!$foodSide) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food side not found'];
    }

    // Check if the user owns the store
    if ($foodSide['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized'];
    }

    $result = $this->foodItem->updateFoodSideStatus($id, 'Inactive');

    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food side deactivated'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to update food side'];
    }
}

public function bulkDeleteFoodSides($ids, $user)
{
    try {
        // Validate input
        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid food side IDs'];
        }

        error_log("bulkDeleteFoodSides controller: Processing " . count($ids) . " IDs: " . implode(',', $ids));

        // Get store_id from database if not in JWT token
        $storeId = $user['store_id'] ?? null;
        if (!$storeId) {
            // Fetch store from database using user_id
            $store = $this->storeModel->getStoreByUserId($user['user_id']);
            if (!$store) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Store not found for user'];
            }
            $storeId = $store['id'];
        }

        error_log("bulkDeleteFoodSides controller: Store ID: $storeId");

        // Fetch all food sides by IDs
        $foodSides = $this->foodItem->getFoodSidesByIds($ids);
        
        if (empty($foodSides)) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'No food sides found with provided IDs'];
        }

        error_log("bulkDeleteFoodSides controller: Found " . count($foodSides) . " food sides");

        // Check if all belong to the user's store
        foreach ($foodSides as $side) {
            if ($side['store_id'] != $storeId) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to modify one or more food sides'];
            }
        }

        error_log("bulkDeleteFoodSides controller: Authorization check passed, proceeding with deletion");

        // Perform bulk delete
        $result = $this->foodItem->bulkDeleteFoodSides($ids);
        
        error_log("bulkDeleteFoodSides controller: Model returned: " . var_export($result, true));
        
        if ($result === false) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete food sides. They may be linked to food items.'];
        }

        http_response_code(200);
        return ['status' => 'success', 'message' => "Food sides deleted successfully"];
        
    } catch (Exception $e) {
        error_log("bulkDeleteFoodSides controller exception: " . $e->getMessage());
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
    }
}

public function bulkActivateFoodSides($ids, $user)
{
    return $this->bulkUpdateFoodSideStatus($ids, 'active', $user);
}

public function bulkDeactivateFoodSides($ids, $user)
{
    return $this->bulkUpdateFoodSideStatus($ids, 'inactive', $user);
}

public function bulkUpdateFoodSideStatus($ids, $status, $user)
{
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid food side IDs'];
    }

    // Fetch food sides by ID and check store ownership
    $foodSides = $this->foodItem->getFoodSidesByIds($ids);

    foreach ($foodSides as $side) {
        if ($side['store_id'] != $user['store_id']) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Unauthorized to update one or more food sides'];
        }
    }

    $updated = $this->foodItem->bulkUpdateFoodSideStatus($ids, $status);
    return [
        'status' => 'success',
        'message' => "$updated food sides updated to $status"
    ];
}


// CREATE Food Item Side Mapping
public function addFoodItemSide($data,$user)
{
    // Validate required fields
    if (!isset($data['item_id'], $data['side_id'], $data['extra_price'])) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Missing required fields: item_id, side_id, extra_price'];
    }

    $result = $this->foodItem->createFoodItemSide($data);
    if ($result) {
        http_response_code(201); // Created
        return ['status' => 'success', 'message' => 'Food item side mapping created successfully'];
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to create food item side mapping'];
    }
}


public function updateFoodItemSide($data, $user)
{
    if (empty($data['item_id']) || empty($data['side_id'])) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Missing item_id or side_id'];
    }

    $itemId = $data['item_id'];
    $sideId = $data['side_id'];
    $extraPrice = $data['extra_price'] ?? null;

    $result = $this->foodItem->updateFoodItemSide($itemId, $sideId, $extraPrice);
    if ($result) {
        http_response_code(200); // OK
        return ['status' => 'success', 'message' => 'Food item side mapping updated successfully'];
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to update food item side mapping'];
    }
}

public function getFoodItemSides($itemId, $user)
{
    if (empty($itemId)) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Missing food item ID'];
    }

    $sides = $this->foodItem->getAllSidesForFoodItem($itemId);
    http_response_code(200); // OK
    return ['status' => 'success', 'data' => $sides];
}

public function deleteFoodItemSide($data, $user)
{
    $itemId = $data['item_id'] ?? null;
    $sideId = $data['side_id'] ?? null;

    if (empty($itemId) || empty($sideId)) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Missing item_id or side_id'];
    }

    // Ownership Check: Ensure the item belongs to the user
    if (!$this->userOwnsItem($itemId, $user['user_id'])) {
        http_response_code(403); // Forbidden
        return ['status' => 'error', 'message' => 'Unauthorized to modify this item.'];
    }

    $result = $this->foodItem->deleteFoodItemSide($itemId, $sideId);
    if ($result) {
        http_response_code(200); // OK
        return ['status' => 'success', 'message' => 'Food item side mapping deleted successfully'];
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to delete food item side mapping'];
    }
}

// CREATE Food Section
public function createFoodSection($data, $user)
{
    if (empty($data['store_id']) || empty($data['section_name'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Please provide the store and section name.'];
    }

    // Validate items array if provided
    if (isset($data['items'])) {
        if (!is_array($data['items'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Items must be an array.'];
        }
        
        // Validate each item in the array
        foreach ($data['items'] as $index => $item) {
            if (!isset($item['name']) || empty($item['name'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "Item at index {$index} must have a name."];
            }
            
            if (!isset($item['price']) || !is_numeric($item['price']) || $item['price'] < 0) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "Item '{$item['name']}' must have a valid price (non-negative number)."];
            }
        }
    }

    if (isset($data['is_required'])) {
        if (!in_array($data['is_required'], [0, 1])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid selection for the Required option.'];
        }

        if ($data['is_required'] == 1) {
            if (!isset($data['max_qty']) || !is_numeric($data['max_qty']) || $data['max_qty'] <= 0) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Please set the maximum quantity since the section is required.' ];
            }
        }
    }

    try {
        $result = $this->foodItem->createFoodSection($data);
        if ($result && is_array($result)) {
            http_response_code(201);
            
            // Extract item IDs from the items array
            $itemIds = [];
            if (isset($result['items']) && is_array($result['items'])) {
                $itemIds = array_column($result['items'], 'id');
            }
            
            return [
                'status' => 'success', 
                'message' => 'Section created successfully.',
                'data' => [
                    'section_id' => $result['id'],
                    'section_name' => $result['section_name'],
                    'required' => $result['required'],
                    'max_quantity' => $result['max_quantity'],
                    'item_ids' => $itemIds,
                    'items' => $result['items']
                ]
            ];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Something went wrong. Unable to create the section. Please try again.'];
        }
    } catch (\Exception $e) {
        // Handle duplicate section name error
        if (strpos($e->getMessage(), 'already exists') !== false) {
            http_response_code(409); // Conflict
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Handle other errors
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Something went wrong. Unable to create the section. Please try again.'];
    }
}


// READ All Sections by Store
public function getAllFoodSectionsByStoreId($user, $page = 1, $limit = 10)
{
    // Extract store_id from authenticated user
    $storeId = $user['store_id'];
    
    if (!$storeId) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found for user'];
    }

    $offset = ($page - 1) * $limit;
    
    $result = $this->foodItem->getAllFoodSectionsByStoreIdPaginated($storeId, $limit, $offset);
    $totalCount = $this->foodItem->countFoodSectionsByStoreId($storeId);
    
    http_response_code(200);
    return [
        'status' => 'success', 
        'data' => $result,
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'total_pages' => ceil($totalCount / $limit),
            'has_next' => ($page * $limit) < $totalCount,
            'has_prev' => $page > 1
        ]
    ];
}

// UPDATE Food Section
public function updateFoodSection($id, $data, $user)
{
    if (empty($id)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Please provide the section ID.'];
    }

    // Authorization check - verify the section belongs to the user's store
    $existingSection = $this->foodItem->getFoodSectionById($id);
    if (!$existingSection) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food section not found'];
    }

    if ($existingSection['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to update this food section.'];
    }

    // Validate items array is required
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Items array is required and must not be empty.'];
    }
    
    // Validate each item in the array
    foreach ($data['items'] as $index => $item) {
        if (!isset($item['name']) || empty($item['name'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => "Item at index {$index} must have a name."];
        }
        
        if (!isset($item['price']) || !is_numeric($item['price']) || $item['price'] < 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => "Item '{$item['name']}' must have a valid price (non-negative number)."];
        }
    }

    // Add section_id to data for the model
    $data['section_id'] = $id;

    $result = $this->foodItem->updateFoodSection($data);
    if ($result && is_array($result)) {
        http_response_code(200);
        
        // Extract item IDs from the items array
        $itemIds = [];
        if (isset($result['items']) && is_array($result['items'])) {
            $itemIds = array_column($result['items'], 'id');
        }
        
        return [
            'status' => 'success', 
            'message' => 'Items added to section successfully.',
            'data' => [
                'section_id' => $result['id'],
                'section_name' => $result['section_name'],
                'required' => $result['required'],
                'max_quantity' => $result['max_quantity'],
                'item_ids' => $itemIds,
                'items' => $result['items']
            ]
        ];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Section not found or could not be updated.'];
    }
}

// get foodsectionbyID

public function getFoodSectionById($id, $user)
{
    if (empty($id)) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Section ID is required'];
    }

    $section = $this->foodItem->getFoodSectionById($id);

    if (!$section) {
        http_response_code(404); // Not Found
        return ['status' => 'error', 'message' => 'Food section not found'];
    }

    // Authorization check - verify the section belongs to the user's store
    if ($section['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to access this food section.'];
    }

    http_response_code(200); // OK
    return ['status' => 'success', 'data' => $section];
}


// DELETE Food Section
public function deleteFoodSection($id, $user)
{
    if (empty($id)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Section ID is required'];
    }

    // Authorization check - verify the section belongs to the user's store
    $existingSection = $this->foodItem->getFoodSectionById($id);
    if (!$existingSection) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food section not found'];
    }

    if ($existingSection['store_id'] != $user['store_id']) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to delete this food section.'];
    }

    $result = $this->foodItem->deleteFoodSection($id);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food section deleted successfully'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to delete food section'];
    }
}


public function getItemsByCategoryInStore($user, $categoryId, $page = 1, $limit = 10)
{
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        return ["status" => "error", "message" => "Access denied. Only merchants can fetch store items."];
    }

    $store = $this->storeModel->getStoreByUserId($user['user_id']);

    if (!$store) {
        http_response_code(404);
        return ["status" => "error", "message" => "Store not found for this user."];
    }
    $storeId = $store['id'];
    $offset = ($page - 1) * $limit;

    //$items = $this->foodItem->getItemsByStoreAndCategory($storeId, $categoryId);
     $items = $this->foodItem->getItemsByStoreAndCategoryPaginated($storeId, $categoryId, $limit, $offset);
    $totalCount = $this->foodItem->countItemsByStoreAndCategory($storeId, $categoryId);

    return ["status" => "success", "data" => $items];
}

public function bulkUpdateCategory($data, $user)
{
    $mode = $data['mode'] ?? 'assign';
    $itemIds = $data['item_ids'] ?? [];
    $newCategoryId = $data['new_category_id'] ?? null;
    $storeId = $user['store_id'] ?? null;

    if (empty($itemIds) || !is_array($itemIds)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Missing or invalid item_ids'];
    }

    if (!$storeId) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found for user'];
    }

    // Only check for new_category_id if mode is assign
    if ($mode === 'assign' && empty($newCategoryId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Missing new_category_id for assign mode'];
    }

    $result = false;

    if ($mode === 'remove') {
        $result = $this->foodItem->removeItemsFromCategory($itemIds, $storeId);
    } elseif ($mode === 'assign') {
        $result = $this->foodItem->updateItemsCategoryBulk($itemIds, $newCategoryId, $storeId);
    } else {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid mode'];
    }

    if ($result) {
        return ['status' => 'success', 'message' => 'Food Items updated successfully'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to update item categories'];
    }
}





    public function bulkActivateItems($data, $user)
    {
        return $this->bulkUpdateStatus($data, $user, 'active');
    }

    public function bulkDeactivateItems($data, $user)
    {
        return $this->bulkUpdateStatus($data, $user, 'inactive');
    }

    // Bulk delete food items
    public function bulkDeleteItems($data, $user)
    {
        if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
        }

        // Check authorization for all items
        foreach ($data['ids'] as $id) {
            if (!$this->userOwnsItem($id, $user['user_id'])) {
                http_response_code(403);
                return ["status" => "error", "message" => "Unauthorized to delete this item"];
            }
        }

        $result = $this->foodItem->deleteItemsBulk($data['ids']);
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food items deleted successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete food items'];
        }
    }

    // Activate single food item
    public function activateFoodItem($data, $user)
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid required field: id."];
        }

        // Authorization check
        if (!$this->userOwnsItem($data['id'], $user['user_id'])) {
            http_response_code(403);
            return ["status" => "error", "message" => "Unauthorized to activate this food item."];
        }

        $result = $this->foodItem->setFoodItemStatus($data['id'], 'active');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food item activated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to activate food item'];
        }
    }

    // Deactivate single food item
    public function deactivateFoodItem($data, $user)
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid required field: id."];
        }

        // Authorization check
        if (!$this->userOwnsItem($data['id'], $user['user_id'])) {
            http_response_code(403);
            return ["status" => "error", "message" => "Unauthorized to deactivate this food item."];
        }

        $result = $this->foodItem->setFoodItemStatus($data['id'], 'inactive');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food item deactivated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to deactivate food item'];
        }
    }

private function bulkUpdateStatus($data, $user, $status)
{
    if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
    }

    foreach ($data['ids'] as $id) {
        if (!$this->userOwnsItem($id, $user['user_id'])) {
            http_response_code(403);
            return ["status" => "error", "message" => "Unauthorized to update this item"];
        }
    }

    return $this->foodItem->updateItemsStatusBulk($data['ids'], $status);
}

public function getCategoriesByStoreType($storeId)
{
    try {
        $sql = "SELECT c.id, c.name, c.description, c.image_url, c.status 
                FROM categories c 
                JOIN store_types st ON c.store_type_id = st.id 
                JOIN stores s ON s.store_type_id = st.id 
                WHERE s.id = :store_id AND c.status = 'active'
                ORDER BY c.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $storeId]);
        $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $categories
        ];
        
    } catch (\Exception $e) {
        error_log("getCategoriesByStoreType error: " . $e->getMessage());
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to fetch categories'];
    }
}

    // Activate single food section
    public function activateFoodSection($data, $user)
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid required field: id."];
        }

        // Authorization check - verify the section belongs to the user's store
        $existingSection = $this->foodItem->getFoodSectionById($data['id']);
        if (!$existingSection) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'Food section not found'];
        }

        if ($existingSection['store_id'] != $user['store_id']) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Unauthorized to activate this food section.'];
        }

        $result = $this->foodItem->setFoodSectionStatus($data['id'], 'active');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food section activated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to activate food section'];
        }
    }

    // Deactivate single food section
    public function deactivateFoodSection($data, $user)
    {
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid required field: id."];
        }

        // Authorization check - verify the section belongs to the user's store
        $existingSection = $this->foodItem->getFoodSectionById($data['id']);
        if (!$existingSection) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'Food section not found'];
        }

        if ($existingSection['store_id'] != $user['store_id']) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Unauthorized to deactivate this food section.'];
        }

        $result = $this->foodItem->setFoodSectionStatus($data['id'], 'inactive');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food section deactivated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to deactivate food section'];
        }
    }

    // Bulk activate food sections
    public function bulkActivateFoodSections($ids, $user)
    {
        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
        }

        // Authorization check - verify all sections belong to the user's store
        foreach ($ids as $id) {
            $existingSection = $this->foodItem->getFoodSectionById($id);
            if (!$existingSection) {
                http_response_code(404);
                return ['status' => 'error', 'message' => "Food section with ID {$id} not found"];
            }

            if ($existingSection['store_id'] != $user['store_id']) {
                http_response_code(403);
                return ['status' => 'error', 'message' => "Unauthorized to activate food section with ID {$id}."];
            }
        }

        $result = $this->foodItem->updateFoodSectionsStatusBulk($ids, 'active');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food sections activated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to activate food sections'];
        }
    }

    // Bulk deactivate food sections
    public function bulkDeactivateFoodSections($ids, $user)
    {
        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
        }

        // Authorization check - verify all sections belong to the user's store
        foreach ($ids as $id) {
            $existingSection = $this->foodItem->getFoodSectionById($id);
            if (!$existingSection) {
                http_response_code(404);
                return ['status' => 'error', 'message' => "Food section with ID {$id} not found"];
            }

            if ($existingSection['store_id'] != $user['store_id']) {
                http_response_code(403);
                return ['status' => 'error', 'message' => "Unauthorized to deactivate food section with ID {$id}."];
            }
        }

        $result = $this->foodItem->updateFoodSectionsStatusBulk($ids, 'inactive');
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food sections deactivated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to deactivate food sections'];
        }
    }

    // Bulk delete food sections
    public function bulkDeleteFoodSections($ids, $user)
    {
        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
        }

        // Authorization check - verify all sections belong to the user's store
        foreach ($ids as $id) {
            $existingSection = $this->foodItem->getFoodSectionById($id);
            if (!$existingSection) {
                http_response_code(404);
                return ['status' => 'error', 'message' => "Food section with ID {$id} not found"];
            }

            if ($existingSection['store_id'] != $user['store_id']) {
                http_response_code(403);
                return ['status' => 'error', 'message' => "Unauthorized to delete food section with ID {$id}."];
            }
        }

        $result = $this->foodItem->deleteFoodSectionsBulk($ids);
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Food sections deleted successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete food sections'];
    }
}

}
?>
