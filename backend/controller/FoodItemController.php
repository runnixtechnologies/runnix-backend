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

        $uploadDir = __DIR__ . '/../../uploads/items/';
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

    // Attach photo to data
    $data['photo'] = $photo;

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

        $uploadDir = __DIR__ . '/../../uploads/items/';
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


// CREATE Food Side
public function createFoodSide($data)
{
    $result = $this->foodItem->createFoodSide($data);
    if ($result) {
        http_response_code(201); // Created
        return ['status' => 'success', 'message' => 'Food side created successfully', 'data' => $result];
    } else {
        http_response_code(500); // Internal Server Error
        return ['status' => 'error', 'message' => 'Failed to create food side'];
    }
}

public function getFoodSideById($id, $user)
{
    if (empty($id)) {
        http_response_code(400); // Bad Request
        return ['status' => 'error', 'message' => 'Missing Food Side ID'];
    }

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
public function getAllFoodSidesByStoreId($storeId)
{
    $result = $this->foodItem->getAllFoodSidesByStoreId($storeId);
    http_response_code(200); // OK
    return ['status' => 'success', 'data' => $result];
}

// UPDATE Food Side
public function updateFoodSide($data)
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
public function deleteFoodSide($id)
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
    $result = $this->foodItem->createFoodSection($data);
    if ($result) {
        http_response_code(201);
        return ['status' => 'success', 'message' => 'Food section created successfully'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to create food section'];
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
    $result = $this->foodItem->updateFoodSection( $data);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Food section updated successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Food section not found or not updated'];
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

}
?>
