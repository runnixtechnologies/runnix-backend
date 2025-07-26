<?php
namespace Controller;

use Model\FoodItem;
use Model\Store;
use Config\JwtHandler;

class FoodItemController
{
    private $foodItem;
    private $storeModel;

    public function __construct()
    {
        $this->foodItem = new FoodItem();
         $this->storeModel = new Store();
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

    if (!$this->storeModel->storeIdExists($data['store_id'])) {
    http_response_code(400); // Bad Request
    return ['status' => 'error', 'message' => 'Invalid store_id. Store does not exist.'];
}

    $result = $this->foodItem->create($data);
    if ($result) {
        http_response_code(201); // Created
        return ['status' => 'success', 'message' => 'Food item created successfully', 'data' => $result];
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to create food item'];
    }
}


   public function update($data, $user)
{
    $photo = null;

    if (!isset($data['id']) || empty($data['id'])) {
       

    return ['status' => 'error', 'message' => 'Food item does not exist'];
    
    }


    // Check if item exists
    if (!$this->foodItem->itemExists($data['id'])) {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food item does not exist in the DB'];
    }
    // Authorization check placeholder (implement your own logic)
    if (!$this->foodItem->isFoodOwnedByUser($data['id'],$user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to update this item."];
    }

    // Handle photo upload if new photo is provided
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

        $photo = $filename;
    }

    // If new photo is uploaded, update the photo field
    if ($photo) {
        $data['photo'] = $photo;
    }

    $result = $this->foodItem->update($data);
    if ($result) {
        http_response_code(200); // OK
        return ['status' => 'success', 'message' => 'Food item updated successfully'];
    } else {
        http_response_code(404); // Not Found
        return ['status' => 'error', 'message' => 'Food item not found or not updated'];
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

    return $this->foodItem->isFoodOwnedByUser($id, $userId);
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

    public function getAllFoodItems($user)
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

    // Get food items using the store_id
    $foodItems = $this->foodItem->getAllByStoreId($storeId);

    http_response_code(200); // OK
    return ["status" => "success", "data" => $foodItems];
}


public function getAllFoodItemsByStoreId($data, $user)
{
    // Validate store_id parameter
    if (!isset($data['store_id']) || empty($data['store_id'])) {
        http_response_code(400); // Bad Request
        return ["status" => "error", "message" => "store_id is required."];
    }

    $storeId = $data['store_id'];

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

    // Fetch food items
    $foodItems = $this->foodItem->getAllByStoreId($storeId);
    if ($foodItems ){
     http_response_code(200);
    return ["status" => "success", "data" => $foodItems];
    }
    else{
         http_response_code(400); // Bad Request
        return ["status" => "error", "message" => "No Food Items Found."];
    }
 
}

// CREATE Food Side
public function createFoodSide($data, $user)
{
    // Ensure store_id
    if (!isset($data['store_id']) || empty($data['store_id'])) {
        $data['store_id'] = $user['store_id'];
    }

    // Validate name
    if (!isset($data['name']) || empty(trim($data['name']))) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food side name is required'];
    }

    // Validate price
    if (!isset($data['price']) || empty($data['price'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Food side price is required'];
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
    
     $side = $this->foodItem->getFoodSideById($id);
       if ($side) {
        http_response_code(200); // OK
        return ['status' => 'success', 'data' => $side];
    } else {
        http_response_code(404); // Not Found
        return ['status' => 'error', 'message' => 'Food Side not found'];
    }

    
   
    
}

// READ All Sides by Store
public function getAllFoodSidesByStoreId($store_id, $user)
{
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;

    $result = $this->foodItem->getAllFoodSidesByStoreId($store_id, $limit, $offset);
    http_response_code(200);
    return ['status' => 'success', 'data' => $result];
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

        // Fetch all food sides by IDs
        $foodSides = $this->foodItem->getFoodSidesByIds($ids);
        
        if (empty($foodSides)) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'No food sides found with provided IDs'];
        }

        // Check if all belong to the user's store
        foreach ($foodSides as $side) {
            if ($side['store_id'] != $storeId) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to modify one or more food sides'];
            }
        }

        // Perform bulk delete
        $result = $this->foodItem->bulkDeleteFoodSides($ids);
        
        if ($result === false) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete food sides. They may be linked to food items.'];
        }

        http_response_code(200);
        return ['status' => 'success', 'message' => "$result food sides deleted successfully"];
        
    } catch (Exception $e) {
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

    if (isset($data['side_ids']) && !is_array($data['side_ids'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid sides format. Please select valid sides from the list.'];
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

    $result = $this->foodItem->createFoodSection($data);
    if ($result) {
        http_response_code(201);
        return ['status' => 'success', 'message' => 'Section created successfully.'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Something went wrong. Unable to create the section. Please try again.'];
    }
}


// READ All Sections by Store
public function getAllFoodSectionsByStoreId($storeId)
{
    $result = $this->foodItem->getAllFoodSectionsByStoreId($storeId);
    http_response_code(200);
    return ['status' => 'success', 'data' => $result];
}

// UPDATE Food Section
public function updateFoodSection($id, $data, $user)
{
    if (empty($id) || empty($data['section_name'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Please provide the section ID and name.'];
    }

    if (isset($data['side_ids']) && !is_array($data['side_ids'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid sides format. Please select valid sides from the list.'];
    }

    if (isset($data['is_required'])) {
        if (!in_array($data['is_required'], [0, 1])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid selection for the Required option.'];
        }

        if ($data['is_required'] == 1) {
            if (!isset($data['max_qty']) || !is_numeric($data['max_qty']) || $data['max_qty'] <= 0) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Please set the maximum quantity since the section is required.'];
            }
        }
    }

    $result = $this->foodItem->updateFoodSection($data);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Section updated successfully.'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Section not found or could not be updated.'];
    }
}

// get foodsectionbyID

public function getFoodSectionById($id)
{
    if (empty($id)) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Section ID is required'];
    }

    $section = $this->foodItem->getFoodSectionById($id);

    if ($section) {
        http_response_code(200); // OK
        return ['status' => 'success', 'data' => $section];
    } else {
        http_response_code(404); // Not Found
        return ['status' => 'error', 'message' => 'Food section not found'];
    }
}


// DELETE Food Section
public function deleteFoodSection($id, $user)
{
    $result = $this->foodItem->deleteFoodSection($id);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food section deleted successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food section not found or not deleted'];
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



public function bulkDeleteItems($data, $user)
{
    if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing or invalid field: ids (array required)."];
    }

    foreach ($data['ids'] as $id) {
        if (!$this->userOwnsItem($id, $user['user_id'])) {
            http_response_code(403);
            return ["status" => "error", "message" => "Unauthorized to delete item ID"];
        }
    }

    return $this->foodItem->deleteItemsBulk($data['ids']);
}

public function bulkActivateItems($data, $user)
{
    return $this->bulkUpdateStatus($data, $user, 'active');
}

public function bulkDeactivateItems($data, $user)
{
    return $this->bulkUpdateStatus($data, $user, 'inactive');
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

}
?>
