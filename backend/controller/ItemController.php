<?php

namespace Controller;

use Model\Item;
use Model\Store;
class ItemController
{
    private $itemModel;
    private $storeModel;
    public function __construct()
    {
        $this->itemModel = new Item();
         $this->storeModel = new Store();
    }

    public function addItemsBulk($data, $user)
{
    if (!isset($data['category_id'], $data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Invalid input: category_id and items[] are required."];
    }

    if (!isset($user['user_id'])) {
        http_response_code(401);
        return ["status" => "error", "message" => "Unauthorized"];
    }

    $store = $this->storeModel->getStoreByUserId($user['user_id']);

    if (!$store) {
        http_response_code(403);
        return ["status" => "error", "message" => "Store not found for user"];
    }

    $storeId = $store['id'];
    $categoryId = $data['category_id'];
    $itemsToInsert = [];

    $allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/pjpeg',
        'image/x-png'
    ];
    $uploadDir = __DIR__ . '/../../uploads/items/';
    $maxSize = 3 * 1024 * 1024; // 3MB

    foreach ($data['items'] as $index => $item) {
        if (!isset($item['name'], $item['price'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Item at index $index is missing name or price."];
        }

        $photoFilename = null;

        // Safely check if a valid file was uploaded for this item
        if (
            isset($_FILES['items']['name'][$index]['photo']) &&
            !empty($_FILES['items']['name'][$index]['photo']) &&
            $_FILES['items']['error'][$index]['photo'] === UPLOAD_ERR_OK
        ) {
            $fileTmpPath = $_FILES['items']['tmp_name'][$index]['photo'];
            $fileName = $_FILES['items']['name'][$index]['photo'];
            $fileType = $_FILES['items']['type'][$index]['photo'];
            $fileSize = $_FILES['items']['size'][$index]['photo'];

            if (!in_array($fileType, $allowedTypes)) {
                http_response_code(415);
                return ["status" => "error", "message" => "Invalid file type at index $index. Only JPG and PNG allowed."];
            }

            if ($fileSize > $maxSize) {
                http_response_code(413);
                return ["status" => "error", "message" => "File size exceeds limit (3MB) at index $index."];
            }

            $photoFilename = uniqid("item_") . '_' . basename($fileName);
            $destPath = $uploadDir . $photoFilename;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                http_response_code(500);
                return ["status" => "error", "message" => "Failed to upload photo at index $index."];
            }
        }

        $itemsToInsert[] = [
    'name' => $item['name'],
    'price' => $item['price'],
    'user_id' => $user['user_id'],
    'photo' => $photoFilename ? 'https://api.runnix.africa/uploads/items/' . $photoFilename : null
];

    }

    return $this->itemModel->bulkCreateItems($storeId, $categoryId, $itemsToInsert);
}


public function createSingleItem($data, $user)
{
    if (!isset($data['category_id'], $data['name'], $data['price'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing required fields: category_id, name, price."];
    }

    // 👇 Use $user instead of $_SESSION
    if (!isset($user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized: User ID missing."];
    }

    // Fetch store_id from DB if you don't store it in token
    $store = $this->storeModel->getStoreByUserId($user['user_id']);
    if (!$store) {
        http_response_code(403);
        return ["status" => "error", "message" => "No store associated with this user. Please complete setup."];
    }

    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // ... existing image validation code ...
           $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/x-png'
        ];

        $fileType = $_FILES['photo']['type'];
        $fileSize = $_FILES['photo']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(415);
            return ["status" => "error", "message" => "Unsupported image format."];
        }

        if ($fileSize > 3 * 1024 * 1024) {
            http_response_code(413);
            return ["status" => "error", "message" => "Image exceeds max size of 3MB."];
        }

        $uploadDir = __DIR__ . '/../../uploads/items/';
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('item_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to upload image."];
        }

       $photo = 'https://api.runnix.africa/uploads/items/' . $filename;

    }

   return $this->itemModel->createSingleItem(
    $store['id'],
    $data['category_id'],
    $user['user_id'],
    $data['name'],
    $data['price'],
    $photo
);

}


    public function updateItem($data, $user)
{
    // Validate required fields and data types
    if (!isset($data['id'], $data['name'], $data['price'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing required fields: id, name, price."];
    }
    if (!is_numeric($data['id']) || !is_string($data['name']) || trim($data['name']) === '' || !is_numeric($data['price']) || $data['price'] < 0) {
        http_response_code(400);
        return ["status" => "error", "message" => "Invalid input data."];
    }

    // Authorization check placeholder (implement your own logic)
    if (!$this->userOwnsItem($data['id'],$user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to update this item."];
    }

    return $this->itemModel->updateItem($data['id'], $data);
}

public function deleteItem($data, $user)
{
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing or invalid required field: id."];
    }

    // Authorization check
   if (!$this->userOwnsItem($data['id'], $user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to delete this item."];
    }

    return $this->itemModel->deleteItem($data['id']);
}

public function deactivateItem($data, $user)
{
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing or invalid required field: id."];
    }

    if (!isset($user['user_id'])) {
        http_response_code(401);
        return ["status" => "error", "message" => "Unauthorized"];
    }

    // Authorization check with JWT user ID
    if (!$this->userOwnsItem($data['id'], $user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to deactivate this item."];
    }

    return $this->itemModel->setItemStatus($data['id'], 'inactive');
}


public function activateItem($data, $user)
{
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing or invalid required field: id."];
    }

    // Authorization check
    if (!$this->userOwnsItem($data['id'], $user['user_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized to activate this item."];
    }

    return $this->itemModel->setItemStatus($data['id'], 'active');
}


// Dummy authorization example — replace with your actual logic
private function userOwnsItem($itemId, $userId)
{
    if (!$userId || !is_numeric($userId)) {
        return false;
    }

    return $this->itemModel->isItemOwnedByUser($itemId, $userId);
}
public function getAllItems($user, $page = 1, $limit = 10)
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

    // Fetch items + total count
    $items = $this->itemModel->getItemsByStoreIdPaginated($storeId, $limit, $offset);
    $totalCount = $this->itemModel->countItemsByStoreId($storeId);

    return [
        "status" => "success",
        "data" => $items,
        "meta" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalCount,
            "total_pages" => ceil($totalCount / $limit)
        ]
    ];
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

    $items = $this->itemModel->getItemsByStoreAndCategoryPaginated($storeId, $categoryId, $limit, $offset);
    $totalCount = $this->itemModel->countItemsByStoreAndCategory($storeId, $categoryId);

    return [
        "status" => "success",
        "data" => $items,
        "meta" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalCount,
            "total_pages" => ceil($totalCount / $limit)
        ]
    ];
}

public function bulkUpdateCategory($data, $user)
{
    $itemIds = $data['item_ids'] ?? [];
    $categoryId = $data['new_category_id'] ?? null;
    $storeId = $user['store_id'] ?? null;

    if (!is_array($itemIds)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'item_ids must be an array'];
    }

    if (!$categoryId || !$storeId) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Missing category or store ID'];
    }

    $result = $this->itemModel->replaceItemsInCategory($itemIds, $categoryId, $storeId);

    if ($result) {
        return ['status' => 'success', 'message' => 'Category items replaced successfully'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to replace category items'];
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

    return $this->itemModel->deleteItemsBulk($data['ids']);
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

    return $this->itemModel->updateItemsStatusBulk($data['ids'], $status);
}


}

