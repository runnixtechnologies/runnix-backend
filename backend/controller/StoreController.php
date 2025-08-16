<?php 
namespace Controller;

use Model\Store;
use Config\JwtHandler;

class StoreController
{
    private $store;

    public function __construct()
    {
        $this->store = new Store();
    }

    public function getStoreByUserId($userId)
    {
        $store = $this->store->getStoreByUserId($userId);
        if ($store) {
            return ["status" => "success", "store" => $store];
        }

        http_response_code(404);
        return ["status" => "error", "message" => "Store not found"];
    }

    
  public function verifyStoreAddress($data)
{
    if (empty($data['store_id']) || empty($data['latitude']) || empty($data['longitude'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing required fields"];
    }

    $storeId = $data['store_id'];
    $latitude = $data['latitude'];
    $longitude = $data['longitude'];

    // Check if store exists
    $store = $this->store->getStoreById($storeId);
    if (!$store) {
        http_response_code(404);
        return ["status" => "error", "message" => "Store does not exist"];
    }

    // Save verification address
    $saved = $this->store->saveVerificationAddress($storeId, $latitude, $longitude);

    if ($saved) {
        http_response_code(200);
        return ["status" => "success", "message" => "Address verification submitted"];
    } else {
        http_response_code(409);
        return ["status" => "error", "message" => "A pending verification already exists"];
    }
}


// StoreTypeController.php
public function getActiveStoreTypes()
{
   
    $types = $this->store->getStoreTypes(); // status = 1

    if (empty($types)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active store types found"];
    }

    return ["status" => "success", "data" => $types];
}

public function getActiveCategories()
{
   
    $types = $this->store->getActiveCategories(); // status = 1

    if (empty($types)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active categories found"];
    }

    return ["status" => "success", "data" => $types];
}


public function setStoreStatus($data, $user) {
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Store ID not found. Please ensure you are logged in as a merchant with a store setup."];
    }
    
    if (!isset($data['is_online'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "is_online is required."];
    }

    // Verify store belongs to the user
    $store = $this->store->getStoreById($user['store_id']);

    if (!$store || $store['user_id'] != $user['user_id']) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized access to store."];
    }

    return $this->store->updateStoreStatus($user['store_id'], $data['is_online']);
}

public function getStatus($user) {
    if (!isset($user['user_id'])) {
        http_response_code(401);
        return ["status" => "error", "message" => "Unauthorized"];
    }

    $storeData = $this->getStoreByUserId($user['user_id']);
    
    if ($storeData['status'] !== 'success') {
        // Return the "Store not found" error from getStoreByUserId
        return $storeData;
    }

    $store = $storeData['store'];
    $storeId = $store['id'];

    return $this->store->getStoreStatus($storeId);
}

public function getActiveCategoriesByStoreType($user)
{
    // Extract store_type_id from authenticated user
    if (!isset($user['store_type_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Store type ID not found. Please ensure you are logged in as a merchant with a store setup."];
    }
    
    $storeTypeId = $user['store_type_id'];
    $categories = $this->store->fetchActiveCategoriesByStoreType($storeTypeId);

    if (empty($categories)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active categories found for this store type"];
    }

    return ["status" => "success", "data" => $categories];
}

}
